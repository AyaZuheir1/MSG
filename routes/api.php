<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\PatientController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');
// Route::post('register',[PatientController::class,'register']); //How to differ from doctor register route ??
Route::post('/doctor/register', [DoctorController::class, 'register']);
Route::post('/patient/login', [AuthController::class, 'login']);
// Route::apiResource('patient',PatientController::class);
// Route::apiResource('doctor',PatientController::class);

// Route::get('reviews',[DoctorController::class,'reviews'])->name('doctor.reviews') ->middleware('Auth:sanctum');
// Route::get('rate',[PatientController::class,'rate'])->name('patient.rating')->middleware('Auth:sanctum');
Route::post('/login', [AuthController::class, 'login']);
Route::post('/patient/register', [PatientController::class, 'register']);

Route::middleware(['auth:sanctum'])->group(function () {
    Route::apiResource('articles', ArticleController::class);
});
Route::post('/doctor/register', [DoctorController::class, 'register']);


Route::middleware(['auth:sanctum'])->group(function () {
    //patient route
    // Route::post('/patient/login', [PatientController::class, 'login']);
    Route::put('/patient/update-profile', [PatientController::class, 'updateProfile']);
    Route::post('/doctors/{doctorId}/rate', [PatientController::class, 'rateDoctor']);
    Route::post('/service/rate', [PatientController::class, 'rateService']);
    //patient appointment route
    Route::get('available-appointments/{doctorId}', [PatientController::class, 'availableAppointments']);
    Route::post('book-appointment/{id}', [PatientController::class, 'bookAppointment']);
    Route::get('appointments', [PatientController::class, 'myAppointments']);
    Route::post('cancel-appointment/{id}', [PatientController::class, 'cancelAppointment']);

    //doctor route
    // Route::post('/doctor/login', [DoctorController::class, 'login']);

    //doctor appointment route
    Route::post('/doctor/schedule', [DoctorController::class, 'addSchedule']);
    Route::get('/doctor/appointments', [DoctorController::class, 'myAppointments']);
    Route::delete('/doctor/appointment/{id}', [DoctorController::class, 'deleteAppointment']);

    //admin route
    Route::put('/admin/approve-doctor/{id}', [AdminController::class, 'approveDoctorRequest']);
    Route::put('/admin/reject-doctor/{id}', [AdminController::class, 'rejectDoctorRequest']);
    Route::get('/doctor-requests', [AdminController::class, 'getDoctorRequests']);

});

