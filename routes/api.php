<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\PatientController;


Route::middleware(['auth:sanctum'])->group(function () {
    
    //Article Routes
    Route::apiResource('articles', ArticleController::class);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::get('/articles/{id}/restore', [ArticleController::class, 'restore']);
    Route::get('/articles/trashed', [ArticleController::class, 'trashedArticle']);
    Route::delete('/articles/{id}/forceDelete', [ArticleController::class, 'forceDelete']);
    // Route::put('articles/{id}', [ArticleController::class,'update']); // override to avoid Not Found error
    Route::get('/download/{filename}', [ChatController::class, 'download']);

    Route::get('/articles/search', [ArticleController::class, 'search']);

    Route::get('/doctors/search', [DoctorController::class, 'searchDoctors']);
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/get-messages/{appointment_id}', [ChatController::class, 'getMessages']);

    // Patient Routes
    Route::prefix('patient')->group(function () {
        Route::get('/profile', [PatientController::class, 'profile']);
        Route::put('/update-profile', [PatientController::class, 'update']);
        Route::post('/doctor/{doctorId}/rate', [PatientController::class, 'rateDoctor']);
        Route::post('/service/rate', [PatientController::class, 'rateService']);

        // Patient Appointments
        Route::get('available-appointments/{doctorId}', [PatientController::class, 'availableAppointments']);
        Route::get('doctorappointments/{doctorId}', [PatientController::class, 'ShowAppointments']);
        Route::post('book-appointment/{id}', [PatientController::class, 'bookAppointment']);
        Route::get('appointments', [PatientController::class, 'doctorAppointments']);
        Route::post('cancel-appointment/{id}', [PatientController::class, 'cancelAppointment']);
    });

    // Doctor Routes
    Route::prefix('doctor')->group(function () {
        Route::get('/profile', [DoctorController::class, 'profile']);
        Route::post('/update-profile', [DoctorController::class, 'update']);
        Route::post('/login', [AuthController::class, 'login'])->name('doctorLogin');


        // Doctor Appointments
        Route::post('/schedule', [DoctorController::class, 'addSchedule']);
        Route::put('/appointments/{appointmentId}', [DoctorController::class, 'updateSchedule']);
        Route::get('/appointments', [DoctorController::class, 'doctorAppointments']);
        Route::delete('/appointment/{id}', [DoctorController::class, 'deleteAppointment']);
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::put('/approve-doctor/{id}', [AdminController::class, 'approveDoctorRequest']);
        Route::put('/reject-doctor/{id}', [AdminController::class, 'rejectDoctorRequest']);
        Route::get('/doctor-requests', [AdminController::class, 'getDoctorRequests']);
    });
});
Route::middleware('auth:sanctum')->post('/send-message', [ChatController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->get('/get-messages/{appointmentId}', [ChatController::class, 'getMessages']);

// Route::post('/select-role', [AuthController::class, 'selectRole']);
Route::post('/patient/register', [PatientController::class, 'register']);
Route::post('/doctor/register', [DoctorController::class, 'register'])->name('requst');
Route::post('/patient/login', [AuthController::class, 'login']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);

//https://medsupport-gaza-cfd5c72a1744.herokuapp.com//api/doctor/register
//https://medsupport-gaza-cfd5c72a1744.herokuapp.com//api/doctor/login