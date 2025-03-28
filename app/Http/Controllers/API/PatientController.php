<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\User;
use App\Models\Doctor;
use App\Models\Review;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
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
        $patient = auth::user()->patient;

        if (!$patient) {
            return response()->json(['error' => 'Patient profile not found'], 404);
        }

        return response()->json([
            'patient' => [
                'id' => $patient->id,
                'user_id' => $patient->user_id,
                'first_name' => $patient->first_name,
                'last_name' => $patient->last_name,
                'email' => $patient->email,
                'age' => $patient->age,
                'gender' => $patient->gender,
                'phone_number' => $patient->phone_number,
                'address' => $patient->address,
            ],
        ]);
    }
    public function register(Request $request)
    {
        if (User::where('email', $request->email)->exists()) {
            return response()->json([
                'error' => 'This email is already registered.'
            ], 401);
        }
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|confirmed',
            'age' => 'required|integer|min:1',
            'gender' => 'required|in:male,female',
            'phone_number' => 'required|string|max:15',
            'address' => 'required|string|max:255',
        ]);

        try {
            DB::beginTransaction();

            $user = User::create([
                'username' => "{$validatedData['first_name']} {$validatedData['last_name']}",
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'role' => 'patient',
            ]);

            // return "d";
            $patient = Patient::create([
                'user_id' => $user->id,
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'age' => $validatedData['age'],
                'gender' => $validatedData['gender'],
                'phone_number' => $validatedData['phone_number'],
                'address' => $validatedData['address'],
            ]);

            $token = $user->createToken('AuthToken')->plainTextToken;

            DB::commit();
            // return $user;
            return response()->json([
                'message' => 'Account created successfully.',
                'token' => $token,
                'user' => $user,
                'patient' => $patient,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);

            // return response()->json(['error' => 'Failed to create account.'], 500);
        }
    }

    public function getSpecializations()
    {
        $specializations = Doctor::select('major')
            ->distinct()
            ->get();

        return response()->json([
            'specializations' => $specializations
        ], 200);
    }
    public function getDoctorsBySpecialization($specialization)
    {
        $doctors = Doctor::where('major', $specialization)->get();

        return response()->json([
            'doctors' => $doctors
        ], 200);
    }
    // public function getDoctorAvailabilityByDay($doctorId, Request $request)
    // {
    //     $date = $request->query('date');

    //     // التحقق من صحة التاريخ المدخل
    //     if (!$date || !Carbon::hasFormat($date, 'Y-m-d')) {
    //         return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
    //     }

    //     // استخراج اليوم من التاريخ
    //     $dayOfWeek = Carbon::parse($date)->format('l'); // (مثلاً: Monday, Tuesday)

    //     // جلب جميع المواعيد المحجوزة لهذا الطبيب في هذا اليوم
    //     $bookedTimes = Appointment::where('doctor_id', $doctorId)
    //         ->whereDate('date', $date)
    //         ->pluck('time') // جلب الأوقات المحجوزة
    //         ->toArray();

    //     // جلب جميع الأوقات التي يعمل بها الطبيب في ذلك اليوم من جدول المواعيد
    //     $allDoctorTimes = Appointment::where('doctor_id', $doctorId)
    //         ->where('day', $dayOfWeek) // تأكد أن لديك حقل `day` في جدول `appointments`
    //         ->pluck('time')
    //         ->toArray();

    //     // الأوقات المتاحة = كل الأوقات التي يعمل بها الطبيب - الأوقات المحجوزة
    //     $availableTimes = array_diff($allDoctorTimes, $bookedTimes);

    //     return response()->json([
    //         'date' => $date,
    //         'day' => $dayOfWeek,
    //         'available_times' => array_values($availableTimes),
    //     ], 200);
    // }

    public function getDoctorAvailabilityByDay($doctorId, Request $request)
    {
        $date = $request->query('date');

        // التحقق من صحة التاريخ المدخل
        if (!$date || !Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
        }

        $allDoctorTimes = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date) 
            ->pluck('time')
            ->toArray();

        $bookedTimes = Appointment::where('doctor_id', $doctorId)
            ->whereDate('date', $date)
            ->whereNotNull('patient_id') 
            ->pluck('time')
            ->toArray();

        // استبعاد الأوقات المحجوزة من الأوقات المتاحة
        $availableTimes = array_diff($allDoctorTimes, $bookedTimes);

        return response()->json([
            'date' => $date,
            'available_times' => array_values($availableTimes),
        ], 200);
    }
    private function updateDoctorAverageRating($doctor_id)
    {
        $doctor = Doctor::find($doctor_id);
        $averageRating = Review::where('doctor_id', $doctor_id)->avg('rate');
        $doctor->average_rating = $averageRating;
        $doctor->save();
    }
    public function rateDoctor(Request $request, $doctorId)
    {
        if (auth::user()->role !== 'patient') {

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $validatedData = $request->validate([
            'rate' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);

        $review = Review::create([
            'doctor_id' => $doctorId,
            'patient_id' => auth::user()->patient->id,
            'rate' => $validatedData['rate'],
            'feedback' => $validatedData['feedback'],
        ]);
        $this->updateDoctorAverageRating($doctorId);


        return response()->json([
            'message' => 'Doctor rated successfully!',
            'review' => $review,
        ]);
    }

    public function rateService(Request $request)
    {
        // return $request;
        // return auth::user();
        if (auth::user()->role !== 'patient') {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        $validatedData = $request->validate([
            'rate' => 'required|integer|min:1|max:5',
            'feedback' => 'nullable|string',
        ]);


        $review = Review::create([
            'patient_id' => auth::user()->patient->id,
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
        if (auth::user()->role !== 'patient') {

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('doctor_id', $doctorId)
            ->where('status', 'Available')
            ->get();

        return response()->json($appointments);
    }

    public function ShowAppointments($doctorId)
    {
        if (auth::user()->role !== 'patient') {

            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        return response()->json($appointments);
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
    public function update(Request $request)
    {
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



    public function bookAppointment(Request $request, $id)
    {
        $appointment = Appointment::findOrFail($id);
        $patientId = Auth::user()->patient->id;

        if ($appointment->status === 'Not Available') {
            return response()->json(['status' => 'error', 'message' => 'Appointment not available'], 400);
        }

        $conflict = Appointment::where('patient_id', $patientId)
            ->where('date', $appointment->date)
            ->where('period', $appointment->period)
            ->where(function ($query) use ($appointment) {
                $query->where('start_time', '<', $appointment->end_time)
                    ->where('end_time', '>', $appointment->start_time);
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have an appointment at this time. Please choose a different time slot.'
            ], 400);
        }

        $appointment->update([
            'patient_id' => $patientId,
            'status' => 'Not Available',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment booked successfully!'
        ]);
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

        return response()->json(['message' => 'Appointment canceled successfully!'], 200);
        return response()->json(['message' => 'Appointment canceled successfully!'], 200);
    }
}
