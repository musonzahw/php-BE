<?php

namespace App\Http\Controllers;

use App\Enums\EmploymentType;
use App\Enums\Gender;
use App\Enums\Role;
use App\Models\Teacher;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rules\Enum;

class SuperAdminController extends Controller
{
    private function ensureSuperAdmin(): ?JsonResponse
    {
        if (auth()->user()->role !== Role::SuperAdmin) {
            return response()->json(['message' => 'Forbidden. Only super admins can manage teachers.'], 403);
        }

        return null;
    }


    public function getAllTeachers(): JsonResponse
    {
        if ($response = $this->ensureSuperAdmin()) {
            return $response;
        }

        $teachers = Teacher::with('user:id,name,email,role,created_at')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'teachers' => $teachers,
            'total'    => $teachers->count(),
        ]);
    }


    public function getTeacher(int $id): JsonResponse
    {
        if ($response = $this->ensureSuperAdmin()) {
            return $response;
        }

        $teacher = Teacher::with('user:id,name,email,role,created_at')->find($id);

        if (! $teacher) {
            return response()->json(['message' => 'Teacher not found'], 404);
        }

        return response()->json(['teacher' => $teacher]);
    }


    public function createTeacher(Request $request): JsonResponse
    {
        if ($response = $this->ensureSuperAdmin()) {
            return $response;
        }

        $validator = Validator::make($request->all(), [
            'first_name'       => 'required|string|max:255',
            'last_name'        => 'required|string|max:255',
            'email'            => 'required|email|max:255|unique:users,email',
            'phone'            => 'required|string|max:30',
            'password'         => 'required|string|min:8',
            'employee_number'  => 'required|string|max:50|unique:teachers,employee_number',
            'gender'           => ['required', new Enum(Gender::class)],
            'date_of_birth'    => 'required|date|before:today',
            'national_id'      => 'required|string|max:50|unique:teachers,national_id',
            'address'          => 'required|string',
            'date_joined'      => 'required|date',
            'qualification'    => 'required|string|max:255',
            'specialization'   => 'required|string|max:255',
            'employment_type'  => ['required', new Enum(EmploymentType::class)],
            'salary'           => 'required|numeric|min:0',
            'profile_photo'    => 'required|image|mimes:jpeg,png,jpg,webp|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $profilePhotoPath = $request->file('profile_photo')->store('teachers/photos', 'public');

        try {
            $teacher = DB::transaction(function () use ($request, $profilePhotoPath) {
                $user = User::create([
                    'name'     => trim($request->first_name . ' ' . $request->last_name),
                    'email'    => $request->email,
                    'role'     => Role::Teacher,
                    'password' => Hash::make($request->password),
                ]);

                return Teacher::create([
                    'user_id'          => $user->id,
                    'first_name'       => $request->first_name,
                    'last_name'        => $request->last_name,
                    'phone'            => $request->phone,
                    'employee_number'  => $request->employee_number,
                    'gender'           => $request->gender,
                    'date_of_birth'    => $request->date_of_birth,
                    'national_id'      => $request->national_id,
                    'address'          => $request->address,
                    'date_joined'      => $request->date_joined,
                    'qualification'    => $request->qualification,
                    'specialization'   => $request->specialization,
                    'employment_type'  => $request->employment_type,
                    'salary'           => $request->salary,
                    'profile_photo'    => $profilePhotoPath,
                ]);
            });
        } catch (\Throwable $e) {
            Storage::disk('public')->delete($profilePhotoPath);

            throw $e;
        }

        return response()->json([
            'message' => 'Teacher created successfully',
            'teacher' => $teacher->load('user:id,name,email,role'),
        ], 201);
    }
}
