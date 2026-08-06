<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\RefreshToken;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Enum;
use Tymon\JWTAuth\Exceptions\JWTException;
use Tymon\JWTAuth\Facades\JWTAuth;

class AuthController extends Controller
{
    private const REFRESH_TOKEN_TTL_DAYS = 30;

    private function issueTokens(User $user): array
    {
        $accessToken  = JWTAuth::fromUser($user);
        $refreshToken = $this->createRefreshToken($user);

        return [
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ];
    }

    private function createRefreshToken(User $user): string
    {
        $plainToken = Str::random(64);

        RefreshToken::updateOrCreate(
            ['user_id' => $user->id],
            [
                'token'      => hash('sha256', $plainToken),
                'revoked'    => false,
                'expires_at' => now()->addDays(self::REFRESH_TOKEN_TTL_DAYS),
            ]
        );

        return $plainToken;
    }
    public function signup(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'name'     => 'required|string|max:255',
            'email'    => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
            'role'     => ['required', new Enum(Role::class)],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $user = User::create([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $request->role,
            'password' => Hash::make($request->password),
        ]);

        return response()->json([
            'message' => 'User registered successfully',
            'user'    => $user,
            ...$this->issueTokens($user),
        ], 201);
    }

    
    public function login(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'email'    => 'required|string|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        try {
            if (! $accessToken = JWTAuth::attempt($request->only('email', 'password'))) {
                return response()->json(['message' => 'Invalid credentials'], 401);
            }
        } catch (JWTException $e) {
            return response()->json(['message' => 'Could not create token'], 500);
        }

        $user = auth()->user();

        $refreshToken = $this->createRefreshToken($user);

        return response()->json([
            'message'       => 'Login successful',
            'user'          => $user,
            'access_token'  => $accessToken,
            'refresh_token' => $refreshToken,
            'token_type'    => 'bearer',
            'expires_in'    => config('jwt.ttl') * 60,
        ]);
    }

    public function refresh(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'refresh_token' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $hashed = hash('sha256', $request->refresh_token);

        $record = RefreshToken::where('token', $hashed)->first();

        if (! $record || ! $record->isValid()) {
            return response()->json(['message' => 'Invalid or expired refresh token'], 401);
        }

        $user = $record->user;

        $record->revoke();

        return response()->json($this->issueTokens($user));
    }

    public function logout(Request $request): JsonResponse
    {
        // Blacklist the current JWT
        JWTAuth::invalidate(JWTAuth::getToken());

        // Revoke the refresh token if provided
        if ($request->refresh_token) {
            $hashed = hash('sha256', $request->refresh_token);
            RefreshToken::where('token', $hashed)->update(['revoked' => true]);
        }

        return response()->json(['message' => 'Logout successful']);
    }

    public function user(): JsonResponse
    {
        return response()->json(auth()->user());
    }
    public function getAllUsers(): JsonResponse
    {
        if (auth()->user()->role !== Role::SuperAdmin) {
            return response()->json(['message' => 'Forbidden. Only super admins can view all users.'], 403);
        }

        $users = User::orderBy('created_at', 'desc')->get();

        return response()->json([
            'users' => $users,
            'total' => $users->count(),
        ]);
    }

    
    public function updateUser(Request $request, int $id): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->id !== $id) {
            return response()->json(['message' => 'Forbidden. You can only update your own account.'], 403);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $validator = Validator::make($request->all(), [
            'name'                  => 'sometimes|string|max:255',
            'email'                 => 'sometimes|string|email|max:255|unique:users,email,' . $id,
            'current_password'      => 'required_with:password|string',
            'password'              => 'sometimes|string|min:8|confirmed',
            'role'                  => ['sometimes', new Enum(Role::class)],
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->filled('password')) {
            if (! Hash::check($request->current_password, $user->password)) {
                return response()->json(['message' => 'Current password is incorrect'], 422);
            }
        }

        if ($request->filled('role') && $caller->role !== Role::SuperAdmin) {
            $requestedRole = Role::from($request->role);
            if ($requestedRole !== $user->role) {
                return response()->json(['message' => 'Forbidden. Only super admins can change roles.'], 403);
            }
        }

        $user->fill(array_filter([
            'name'     => $request->name,
            'email'    => $request->email,
            'role'     => $caller->role === Role::SuperAdmin ? $request->role : null,
            'password' => $request->filled('password') ? Hash::make($request->password) : null,
        ], fn($value) => ! is_null($value)));

        $user->save();

        return response()->json([
            'message' => 'Account updated successfully',
            'user'    => $user->fresh(),
        ]);
    }

    
    public function deleteUser(int $id): JsonResponse
    {
        $caller = auth()->user();

        // Only super_admin and admin can delete users
        if (! in_array($caller->role, [Role::SuperAdmin, Role::Admin])) {
            return response()->json(['message' => 'Forbidden. You do not have permission to delete users.'], 403);
        }

        $user = User::find($id);

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        if ($user->id === $caller->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 403);
        }

        if ($caller->role === Role::Admin && ! in_array($user->role->value, Role::adminDeletable())) {
            return response()->json(['message' => 'Forbidden. Admins can only delete parents and students.'], 403);
        }

        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }
}
