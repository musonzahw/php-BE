<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\ClassEnrollment;
use App\Models\SchoolClass;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassController extends Controller
{

    public function index(): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can view their classes.'], 403);
        }

        $classes = SchoolClass::where('teacher_id', $caller->id)
            ->withCount('enrollments')
            ->orderBy('created_at', 'desc')
            ->get();

        return response()->json([
            'total'   => $classes->count(),
            'classes' => $classes,
        ]);
    }


    public function store(Request $request): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can create classes.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'class_code'    => 'required|string|max:50|unique:classes,class_code',
            'academic_year' => 'required|string|max:20',
            'description'   => 'nullable|string',
            'capacity'      => 'required|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $teacherId = $caller->id;

        $class = SchoolClass::create([
            'name'          => $request->name,
            'class_code'    => $request->class_code,
            'academic_year' => $request->academic_year,
            'description'   => $request->description,
            'capacity'      => $request->capacity,
            'teacher_id'    => $teacherId,
        ]);

        return response()->json([
            'message' => 'Class created successfully',
            'class'   => $class->load('teacher:id,name,email'),
        ], 201);
    }


    public function update(Request $request, int $id): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can update classes.'], 403);
        }

        $class = SchoolClass::find($id);

        if (! $class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        if ($class->teacher_id !== $caller->id) {
            return response()->json(['message' => 'Forbidden. You can only update your own classes.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'name'          => 'sometimes|string|max:255',
            'class_code'    => 'sometimes|string|max:50|unique:classes,class_code,' . $id,
            'academic_year' => 'sometimes|string|max:20',
            'description'   => 'nullable|string',
            'capacity'      => 'sometimes|integer|min:1',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $class->fill(array_filter([
            'name'          => $request->name,
            'class_code'    => $request->class_code,
            'academic_year' => $request->academic_year,
            'description'   => $request->description,
            'capacity'      => $request->capacity,
        ], fn($value) => ! is_null($value)));

        $class->save();

        return response()->json([
            'message' => 'Class updated successfully',
            'class'   => $class->fresh()->load('teacher:id,name,email'),
        ]);
    }


    public function destroy(int $id): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can delete classes.'], 403);
        }

        $class = SchoolClass::find($id);

        if (! $class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        if ($class->teacher_id !== $caller->id) {
            return response()->json(['message' => 'Forbidden. You can only delete your own classes.'], 403);
        }

        $class->delete();

        return response()->json(['message' => 'Class deleted successfully']);
    }


    public function enroll(Request $request, int $id): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can enroll students.'], 403);
        }

        $class = SchoolClass::find($id);

        if (! $class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        if ($class->teacher_id !== $caller->id) {
            return response()->json(['message' => 'Forbidden. You can only enroll students in your own classes.'], 403);
        }

        $validator = Validator::make($request->all(), [
            'student_ids'   => 'required|array|min:1',
            'student_ids.*' => 'integer|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $currentCount = $class->enrollments()->count();
        $newCount     = count($request->student_ids);

        if ($currentCount + $newCount > $class->capacity) {
            return response()->json([
                'message'   => 'Class capacity exceeded.',
                'capacity'  => $class->capacity,
                'enrolled'  => $currentCount,
                'available' => $class->capacity - $currentCount,
            ], 422);
        }

        $enrolled        = 0;
        $alreadyEnrolled = 0;

        foreach ($request->student_ids as $studentId) {
            $student = User::find($studentId);

            if ($student->role !== Role::Student) {
                continue;
            }

            $exists = ClassEnrollment::where('class_id', $id)
                ->where('student_id', $studentId)
                ->exists();

            if ($exists) {
                $alreadyEnrolled++;
                continue;
            }

            ClassEnrollment::create([
                'class_id'   => $id,
                'student_id' => $studentId,
            ]);

            $enrolled++;
        }

        return response()->json([
            'message'          => 'Students enrolled successfully',
            'enrolled'         => $enrolled,
            'already_enrolled' => $alreadyEnrolled,
            'class'            => $class->load('enrollments.student:id,name,email'),
        ]);
    }

    public function students(int $id): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can view class students.'], 403);
        }

        $class = SchoolClass::find($id);

        if (! $class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        if ($class->teacher_id !== $caller->id) {
            return response()->json(['message' => 'Forbidden. You can only view students in your own classes.'], 403);
        }

        $students = $class->enrollments()
            ->with('student:id,name,email')
            ->get()
            ->map(fn($e) => [
                'enrollment_id' => $e->id,
                'id'            => $e->student->id,
                'name'          => $e->student->name,
                'email'         => $e->student->email,
                'enrolled_at'   => $e->created_at,
            ]);

        return response()->json([
            'class'          => $class->name,
            'total_students' => $students->count(),
            'capacity'       => $class->capacity,
            'students'       => $students,
        ]);
    }

    public function removeStudent(int $id, int $studentId): JsonResponse
    {
        $caller = auth()->user();

        if ($caller->role !== Role::Teacher) {
            return response()->json(['message' => 'Forbidden. Only teachers can remove students.'], 403);
        }

        $class = SchoolClass::find($id);

        if (! $class) {
            return response()->json(['message' => 'Class not found'], 404);
        }

        if ($class->teacher_id !== $caller->id) {
            return response()->json(['message' => 'Forbidden. You can only manage your own classes.'], 403);
        }

        $enrollment = ClassEnrollment::where('class_id', $id)
            ->where('student_id', $studentId)
            ->first();

        if (! $enrollment) {
            return response()->json(['message' => 'Student is not enrolled in this class.'], 404);
        }

        $enrollment->delete();

        return response()->json(['message' => 'Student removed from class successfully']);
    }
}
