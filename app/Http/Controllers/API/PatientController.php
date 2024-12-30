<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Review;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Routing\Controllers\Middleware;

class PatientController extends Controller
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['register'])
        ];
    }

    public function profile(Request $request)
    {
        $patient = $request->user()->patient; // جلب بيانات المريض المرتبطة بالمستخدم
        return response()->json($patient);
    }
    public function register(Request $request)
    {
        $Validated =  $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed',
            'SSN' => 'required|string',
            'age' => 'required|integer',
            'gender' => 'required|in:male,female',
            'phone_number' => 'required|string',
            'address' => 'required|string',
        ]);
        try {
            DB::beginTransaction();
            $user = User::create([
                'username' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'patient',
            ]);
            $patient = Patient::create(
                [
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->first_name,
                    'SSN' => $request->SSN,
                    'age' => $request->age,
                    'gender' => $request->gender,
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                ]
            );
            $token = $user->createToken($request->first_name)->plainTextToken;
            DB::commit();
            return response()->json([
                'message' => 'Account created successfully.',
                'user' => $user,
                'patient' => $patient,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }


    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->where('role', 'patient')->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Invalid credentials'], 401);
        }

        // إنشاء Token للمريض
        $token = $user->createToken('patient-auth-token')->plainTextToken;

        // إرجاع البيانات مع الـ Token
        return response()->json([
            'message' => 'Login successful.',
            'token' => $token,
            'user' => $user,
        ], 200);
    }


    public function updateProfile(Request $request)
    {
        // التحقق من البيانات المدخلة
        $validated = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'age' => 'nullable|integer|min:0',
            'gender' => 'nullable|in:male,female',
        ]);

        $patient = Patient::where('user_id', Auth::id())->first();


        if (!$patient) {
            return response()->json(['message' => 'Patient not found.'], 404);
        }
        $patient->update($validated);

        



        return response()->json([
            'message' => 'Profile updated successfully.',
            'patient' => $patient,
        ], 200);
    }


    public function rateDoctor(Request $request, $doctorId)
    {
        if (auth::user()->role !== 'patient') {

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'rate' => 'required|integer|min:1|max:5', // التقييم بين 1 و 5
            'feedback' => 'nullable|string',
        ]);

        // إنشاء التقييم
        $review = Review::create([
            'doctor_id' => $doctorId,
            'patient_id' => auth::id(),
            'rate' => $validatedData['rate'],
            'feedback' => $validatedData['feedback'],
        ]);

        return response()->json([
            'message' => 'Doctor rated successfully!',
            'review' => $review,
        ]);
    }

    public function rateService(Request $request)
    {
        $validatedData = $request->validate([
            'rate' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);

        $review = Review::create([
            'patient_id' => auth::id(),
            'rate' => $validatedData['rate'],
            'feedback' => $validatedData['feedback'],
        ]);

        return response()->json([
            'message' => 'Service rated successfully!',
            'review' => $review,
        ]);
    }


    public function availableAppointments($doctorId)
    {

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'Available')
            ->get();

        return response()->json($appointments);
    }



    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $patient = Patient::where('id', $id);
        return [
            'code' => 200,
            'message' => 'success',
            'patient' => $patient,
        ];
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }

    public function bookAppointment(Request $request, $id)
    {

        $appointment = Appointment::findOrFail($id);

        if ($appointment->status === 'Not Available') {
            return response()->json(['message' => 'Appointment not available'], 400);
        }

        $appointment->update([
            'patient_id' => Auth::user()->patient->id,
            'status' => 'Not Available',
        ]);

        return response()->json(['message' => 'Appointment booked successfully!']);
    }
    public function myAppointments()
    {

        $patientId = auth::user()->patient->id;
        $appointments = Appointment::where('patient_id', $patientId)->get();

        return response()->json($appointments);
    }
    public function cancelAppointment($id)
    {

        $appointment = Appointment::findOrFail($id);

        if ($appointment->patient_id !== auth::user()->patient->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->update([
            'patient_id' => null,
            'status' => 'Available',
        ]);

        return response()->json(['message' => 'Appointment canceled successfully!']);
    }
}
