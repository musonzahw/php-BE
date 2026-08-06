<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AdmissionController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\SuperAdminController;

Route::post('/auth/signup',   [AuthController::class, 'signup']);
Route::post('/auth/login',    [AuthController::class, 'login']);
Route::post('/auth/refresh',  [AuthController::class, 'refresh']);

Route::middleware('auth:api')->group(function () {
    Route::post('/auth/logout',   [AuthController::class, 'logout']);
    Route::get('/auth/user',      [AuthController::class, 'user']);
    Route::get('/users',          [AuthController::class, 'getAllUsers']);
    Route::put('/users/{id}',     [AuthController::class, 'updateUser']);
    Route::delete('/users/{id}',  [AuthController::class, 'deleteUser']);


    Route::prefix('super-admin')->group(function () {
        Route::get('/teachers',      [SuperAdminController::class, 'getAllTeachers']);
        Route::post('/teachers',     [SuperAdminController::class, 'createTeacher']);
        Route::get('/teachers/{id}', [SuperAdminController::class, 'getTeacher']);
    });

    Route::post('/admissions', [AdmissionController::class, 'apply']);
    Route::get('/classes',                                [ClassController::class, 'index']);
    Route::post('/classes',                               [ClassController::class, 'store']);
    Route::put('/classes/{id}',                           [ClassController::class, 'update']);
    Route::delete('/classes/{id}',                        [ClassController::class, 'destroy']);
    Route::post('/classes/{id}/enroll',                   [ClassController::class, 'enroll']);
    Route::get('/classes/{id}/students',                  [ClassController::class, 'students']);
    Route::delete('/classes/{id}/students/{studentId}',   [ClassController::class, 'removeStudent']);
});
