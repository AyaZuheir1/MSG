<?php

namespace App\Http\Controllers\Api;

use Carbon\Carbon;
use App\Models\Patient;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class AppointmentController extends Controller
{
    //     public function book(Request $request)
    // {
    //     // التحقق من البيانات المدخلة
    //     $request->validate([
    //         'doctor_id' => 'required|exists:doctors,id',
    //         'date' => 'required|date|after_or_equal:today',
    //         'time' => 'required|date_format:H:i',
    //     ]);

    //     // التأكد من أن المريض مسجل
    //     $patient = Patient::where('user_id', auth()->id())->first();
    //     if (!$patient) {
    //         return response()->json(['message' => 'Patient not found.'], 404);
    //     }

    //     // التحقق من عدم وجود حجز بنفس اليوم والوقت لنفس الطبيب
    //     $exists = Appointment::where('doctor_id', $request->doctor_id)
    //         ->where('date', $request->date)
    //         ->where('time', $request->time)
    //         ->exists();

    //     if ($exists) {
    //         return response()->json(['message' => 'This time slot is already booked.'], 409);
    //     }

    //     // إنشاء الحجز
    //     $appointment = Appointment::create([
    //         'patient_id' => $patient->id,
    //         'doctor_id' => $request->doctor_id,
    //         'date' => $request->date,
    //         'time' => $request->time,
    //         'status' => 'pending',
    //     ]);

    //     return response()->json([
    //         'message' => 'Appointment booked successfully.',
    //         'appointment' => $appointment,
    //     ], 201);
    // }



    public function cancelAppointment(Request $request)
{
    $validated = $request->validate([
        'appointment_id' => 'required|exists:appointments,id',
    ]);

    $appointment = Appointment::find($validated['appointment_id']);

    if ($appointment->status !== 'booked' || $appointment->patient_id !== auth::id()) {
        return response()->json(['message' => 'Unauthorized or invalid operation'], 403);
    }

    $appointment->update([
        'patient_id' => null,
        'status' => 'available',
        'note' => null,
    ]);

    return response()->json(['message' => 'Appointment cancelled successfully', 'appointment' => $appointment]);
}


}