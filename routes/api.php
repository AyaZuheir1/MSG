<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\DoctorController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\PatientController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// Route::post('register',[PatientController::class,'register']); //How to differ from doctor register route ??
Route::post('/doctor/register',[DoctorController::class,'register']);
Route::post('/patient/register',[PatientController::class,'register']);
Route::post('/patient/login',[AuthController::class,'login'])->middleware('Auth:sanctum');
Route::apiResource('patient',PatientController::class);
Route::apiResource('doctor',PatientController::class);

Route::get('reviews',[DoctorController::class,'reviews'])->name('doctor.reviews') ->middleware('Auth:sanctum');
Route::get('rate',[PatientController::class,'rate'])->name('patient.rating')->middleware('Auth:sanctum');

