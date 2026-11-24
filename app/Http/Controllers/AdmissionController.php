<?php

namespace App\Http\Controllers;

use App\Enums\Role;
use App\Models\Admission;
use App\Models\SchoolClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdmissionController extends Controller
{

    public function apply(Request $request): JsonResponse
    {
        if (auth()->user()->role !== Role::Student) {
            return response()->json(['message' => 'Forbidden. Only students can apply for admission.'], 403);
        }
        $validator = Validator::make($request->all(), [
            'name'         => 'required|string|max:255',
            'surname'      => 'required|string|max:255',
            'class_id'     => 'required|integer|exists:classes,id',
            'results_file' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:2048',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        $class = SchoolClass::find($request->class_id);

        $enrolledCount = $class->enrollments()->count();
        if ($enrolledCount >= $class->capacity) {
            return response()->json([
                'message'  => 'This class is already at full capacity.',
                'capacity' => $class->capacity,
            ], 422);
        }

        $resultsFilePath = null;

        if ($request->hasFile('results_file')) {
            $resultsFilePath = $request->file('results_file')
                ->store('admissions/results', 'public');
        }

        $admission = Admission::create([
            'name'         => $request->name,
            'surname'      => $request->surname,
            'class_id'     => $request->class_id,
            'results_file' => $resultsFilePath,
            'status'       => 'pending',
        ]);

        return response()->json([
            'message'   => 'Admission application submitted successfully',
            'admission' => $admission->load('class:id,name,academic_year'),
        ], 201);
    }
}
