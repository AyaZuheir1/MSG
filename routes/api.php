<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\API\ChatController;
use App\Http\Controllers\API\AdminController;
use App\Http\Controllers\Api\DoctorController;
use App\Http\Controllers\Api\ArticleController;
use App\Http\Controllers\Api\PatientController;
use App\Http\Controllers\DoctorFileController;
use Kreait\Firebase\Factory;

Route::get('/articles/trashed', [ArticleController::class, 'trashedArticle']);
Route::middleware(['auth:sanctum'])->group(function () {
    
    Route::apiResource('articles', ArticleController::class);
    Route::post('/articles/{article}', [ArticleController::class, 'update']);
    Route::get('/articles/{id}/restore', [ArticleController::class, 'restore']);
    Route::delete('/articles/{id}/forceDelete', [ArticleController::class, 'forceDelete']);
    // Route::put('articles/{id}', [ArticleController::class,'update']); // override to avoid Not Found error
    Route::get('/articles/search', [ArticleController::class, 'search']);
    // Route::get('/articles/{id}/restore', [ArticleController::class, 'restore']);
    // Route::get('/articles/trashed', [ArticleController::class, 'trashedArticle']);
    // Route::get('/articles/{id}/forceDelete', [ArticleController::class, 'forceDelete']);
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
        Route::get('appointments', [PatientController::class, 'myAppointments']);
        Route::post('cancel-appointment/{id}', [PatientController::class, 'cancelAppointment']);
    });

    // Doctor Routes
    Route::prefix('doctor')->group(function () {
        Route::get('/profile', [DoctorController::class, 'profile']);
        Route::post('/update-profile', [DoctorController::class, 'update']);

        // Doctor Appointments
        Route::post('/schedule', [DoctorController::class, 'addSchedule']);
        Route::put('/appointments/{appointmentId}', [DoctorController::class, 'updateSchedule']);
        Route::get('/appointments', [DoctorController::class, 'myAppointments']);
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

Route::post('/select-role', [AuthController::class, 'selectRole']);
Route::post('/patient/register', [PatientController::class, 'register']);
Route::post('/doctor/register', [DoctorController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);
Route::post('/verify-otp', [AuthController::class, 'verifyOtp']);
Route::post('/reset-password', [AuthController::class, 'resetPassword'])->name('password.reset');
Route::post('/resendOtp', [AuthController::class, 'resendOtp']);



Route::post('/doctor/upload-file', [DoctorFileController::class, 'upload']);
Route::get('/doctor/{doctor_id}/files', [DoctorFileController::class, 'listFiles']);

Route::get('/test-firebase', function () {
    try {
        // return config('firebase.project_id');
        // return config('firebase.database_url');
        // $firebaseCredentialsPath = storage_path('app/medsg-85fd8-firebase-adminsdk-6dvwn-789bbc02c8.json');

        // if (!file_exists($firebaseCredentialsPath)) {
        //     throw new Exception("Firebase credentials file is missing: $firebaseCredentialsPath");
        // }
        // $firebase = (new Factory)
        // ->withServiceAccount(config('firebase.credentials'))
        
        $firebase = (new Factory)
        ->withServiceAccount(storage_path('app/medsg-85fd8-firebase-adminsdk-6dvwn-789bbc02c8.json'))
        ->withDatabaseUri(config('firebase.database_url'));

        // $this->firebaseStorage = $firebase->createStorage();
        $firebaseDatabase = $firebase->createDatabase();

        $firebaseDatabase->getReference('doctors')->push([
            "name" => "A",
            "type" => "A",
        ]);

        $reference = $firebaseDatabase->getReference('doctor');
        return response()->json($reference->getValue());
        // return config('firebase.credentials');
        // $firebase = (new Factory)
        //     ->withServiceAccount(config('firebase.credentials'));
        // ->withProjectId(env('FIREBASE_PROJECT_ID', 'medsg-85fd8'));

        $auth = $firebase->createAuth();


        return $firebase;
    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
});
