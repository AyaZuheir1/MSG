<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\API\DoctorController;
use App\Http\Controllers\API\ArticleController;
use App\Http\Controllers\API\PatientController;
use App\Http\Controllers\API\AppointmentController;

Route::post('/login', [AuthController::class, 'login']);
Route::post('/doctor/login', [AuthController::class, 'login']);
Route::post('/patient/login', [AuthController::class, 'login']);

Route::middleware(['auth:sanctum'])->group(function () {

    //Article Routes
    Route::apiResource('articles', ArticleController::class);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::get('/articles/{id}/restore', [ArticleController::class, 'restore']);
    Route::get('/articles/trashed', [ArticleController::class, 'trashedArticle']);
    Route::delete('/articles/{id}/forceDelete', [ArticleController::class, 'forceDelete']);
    // Route::put('articles/{id}', [ArticleController::class,'update']); // override to avoid Not Found error
    Route::get('/download/{filename}', [ChatController::class, 'download']);

    Route::get('/article/search', [ArticleController::class, 'search']);

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
        Route::get('available-appointments/{doctorId}', [AppointmentController::class, 'availableAppointments']);
        Route::get('doctorappointments/{doctorId}', [AppointmentController::class, 'ShowAppointments']);
        Route::post('book-appointment/{id}', [AppointmentController::class, 'bookAppointment']);
        Route::get('appointments', [AppointmentController::class, 'myAppointments']);
        Route::post('cancel-appointment/{id}', [AppointmentController::class, 'cancelAppointment']);
        Route::get('/specializations', [AppointmentController::class, 'getSpecializations']);
        Route::get('/doctors/{specialization}', [AppointmentController::class, 'getDoctorsBySpecialization']);
        Route::get('/doctors/{doctorId}/availability', [AppointmentController::class, 'getDoctorAvailabilityByDay']);
    });

    // Doctor Routes
    Route::prefix('doctor')->group(function () {
        Route::get('/profile', [DoctorController::class, 'profile']);
        Route::post('/update-profile', [DoctorController::class, 'update']);


        // Doctor Appointments
        Route::post('/schedule', [AppointmentController::class, 'addSchedule']);
        Route::put('/appointments/{appointmentId}', [AppointmentController::class, 'updateSchedule']);
        Route::get('/appointments', [AppointmentController::class, 'doctorAppointments']);
        Route::delete('/appointment/{id}', [AppointmentController::class, 'deleteAppointment']);
        Route::get('/appointments/pending', [AppointmentController::class, 'getPendingAppointments']);
        Route::put('/appointment/accept/{appointmentId}', [AppointmentController::class, 'acceptAppointment']);
        Route::put('/appointment/reject/{appointmentId}', [AppointmentController::class, 'rejectAppointment']);
    });

    // Admin Routes
    Route::prefix('admin')->group(function () {
        Route::get('/users-list', [AdminController::class, 'getUsersList']);
        Route::put('/approve-doctor/{id}', [AdminController::class, 'approveDoctorRequest']);
        Route::put('/reject-doctor/{id}', [AdminController::class, 'rejectDoctorRequest']);
        Route::get('/doctor-requests', [AdminController::class, 'getDoctorRequests']);
    });
});
Route::middleware('auth:sanctum')->post('/send-message', [ChatController::class, 'sendMessage']);
Route::middleware('auth:sanctum')->get('/get-messages/{appointmentId}', [ChatController::class, 'getMessages']);

// Route::post('/select-role', [AuthController::class, 'selectRole']);
Route::post('/patient/register', [PatientController::class, 'register']);
Route::post('/doctor/register', [DoctorController::class, 'register']);
Route::get('/doctor/register', [DoctorController::class, 'register']);
Route::post('/patient/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::get('/reset-password', [AuthController::class, 'resetPassword']);
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);

//https://medsupport-gaza-cfd5c72a1744.herokuapp.com//api/doctor/register
//https://medsupport-gaza-cfd5c72a1744.herokuapp.com//api/doctor/login