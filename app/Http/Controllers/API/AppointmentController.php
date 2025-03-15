<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\Patient;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class AppointmentController extends Controller
{
    public function addSchedule(Request $request)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:h:i A',
            'end_time' => 'required|date_format:h:i A',
        ]);

        $doctorId = Auth::user()->doctor->id;

        $startTime24 = Carbon::createFromFormat('h:i A', $validated['start_time'])->format('H:i');
        $endTime24 = Carbon::createFromFormat('h:i A', $validated['end_time'])->format('H:i');

        if (Carbon::parse($startTime24)->greaterThanOrEqualTo(Carbon::parse($endTime24))) {
            return response()->json([
                'status' => 'error',
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        $appointmentStart = Carbon::parse($validated['date'] . ' ' . $startTime24);
        if ($appointmentStart->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected appointment time has already passed. Please choose a future time.'
            ], 422);
        }

        $conflict = Appointment::where('doctor_id', $doctorId)
            ->where('date', $validated['date'])
            ->where(function ($query) use ($startTime24, $endTime24) {
                $query->where(function ($q) use ($startTime24, $endTime24) {
                    $q->where('start_time', '<', $endTime24)
                        ->where('end_time', '>', $startTime24);
                });
            })->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected time slot is unavailable due to a scheduling conflict. Please choose a different time.'
            ], 422);
        }

        $appointment = Appointment::create([
            'doctor_id' => $doctorId,
            'date' => $validated['date'],
            'start_time' => $startTime24,
            'end_time' => $endTime24,
            'period' => $request->period,
            'status' => 'Available',
            'period' => 'gaza',
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule added successfully!',
            'appointment' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ], 201);
    }

    public function doctorAppointments(Request $request)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $doctorId = Auth::user()->doctor->id;
        $status = $request->query('status', 'all');
        $appointment = Appointment::where('doctor_id', $doctorId)
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();



        $appointmentsData = $appointment->map(function ($appointment) {
            $patient_id = $appointment->patient_id;
            $patient = Patient::find($patient_id);
            $patient_name = null;
            if($patient){
                $patient_name = $patient->first_name;
            }
            return [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'patient_name' => $patient_name,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i:s', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i:s', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ];
        });
       
        return response()->json([
            'status' => 'success',
            'appointments' => $appointmentsData,
        ]);
    }

    public function deleteAppointment($id)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $appointment = Appointment::findOrFail($id);
        if (auth::user()->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized. User is not a doctor.'], 403);
        }

        if ($appointment->doctor_id !== auth::user()->doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully!']);
    }
    public function updateSchedule(Request $request, $appointmentId)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'start_time' => 'required|date_format:h:i A',
            'end_time' => 'required|date_format:h:i A',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);

        $doctorId = Auth::user()->doctor->id;
        if ($appointment->doctor_id !== $doctorId) {
            return response()->json([
                'status' => 'error',
                'message' => 'You do not have permission to modify this schedule.'
            ], 403);
        }

        if ($appointment->is_accepted == 'accepted') {
            return response()->json([
                'status' => 'error',
                'message' => 'This appointment has already been accepted and cannot be modified.'
            ], 422);
        }
        if ($appointment->status !== 'Available') {
            return response()->json([
                'status' => 'error',
                'message' => 'This appointment has already been booked and cannot be modified.'
            ], 422);
        }

        $startTime24 = Carbon::createFromFormat('h:i A', $validated['start_time'])->format('H:i');
        $endTime24 = Carbon::createFromFormat('h:i A', $validated['end_time'])->format('H:i');

        if (Carbon::parse($startTime24)->greaterThanOrEqualTo(Carbon::parse($endTime24))) {
            return response()->json([
                'status' => 'error',
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        $appointmentStart = Carbon::parse($validated['date'] . ' ' . $startTime24);
        if ($appointmentStart->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected appointment time has already passed. Please choose a future time.'
            ], 422);
        }

        $conflict = Appointment::where('doctor_id', $doctorId)
            ->where('date', $validated['date'])
            ->where('id', '!=', $appointmentId)
            ->where(function ($query) use ($startTime24, $endTime24) {
                $query->where(function ($q) use ($startTime24, $endTime24) {
                    $q->where('start_time', '<', $endTime24)
                        ->where('end_time', '>', $startTime24);
                });
            })->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected time slot is unavailable due to a scheduling conflict. Please choose a different time.'
            ], 422);
        }

        $appointment->update([
            'date' => $validated['date'],
            'start_time' => $startTime24,
            'end_time' => $endTime24,
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule updated successfully!',
            'appointment' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ]);
    }

    public function getPendingAppointments()
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $pendingAppointments = Appointment::where('is_accepted', 'pending')->get();


        $appointments = $pendingAppointments->map(function ($appointment) {
            return [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i:s', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i:s', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ];
        });

        return response()->json([
            'status' => 'success',
            'appointments' => $appointments,
        ]);
    }
    public function acceptAppointment($appointmentId)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->is_accepted != 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'This appointment cannot be accepted because it is not pending.'
            ], 422);
        }

        $appointment->is_accepted = 'accepted';
        $appointment->status = 'Not Available';
        $appointment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment accepted successfully!',
            'appointments' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i:s', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i:s', $appointment->end_time)->format('h:i A'),

                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ]);
    }
    public function rejectAppointment($appointmentId)
    {
        if (!Gate::allows('manage-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $appointment = Appointment::findOrFail($appointmentId);

        if ($appointment->is_accepted != 'pending') {
            return response()->json([
                'status' => 'error',
                'message' => 'This appointment cannot be rejected because it is not pending.'
            ], 422);
        }

        $appointment->is_accepted = 'rejected';
        $appointment->status = 'Available';

        $appointment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment rejected successfully!',
            'appointments' => [
                'id' => $appointment->id,
                'doctor_id' => $appointment->doctor_id,
                'date' => $appointment->date,
                'start_time' => Carbon::createFromFormat('H:i:s', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i:s', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ]);
    }





    public function availableAppointments($doctorId)
    {
        if (!Gate::allows('manage-their-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->whereNull('patient_id')
            ->where('status', 'Available')
            ->get();

        return response()->json($appointments);
    }

    public function showAppointments($doctorId)
    {
        if (!Gate::allows('manage-their-schedule')) {
            abort(403, 'Unauthorized action.');
        }
        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        return response()->json($appointments);
    }

    public function bookAppointment(Request $request, $id)
    {
        if (!Gate::allows('manage-their-schedule')) {
            abort(403, 'Unauthorized action.');
        }

        $appointment = Appointment::findOrFail($id);
        $patientId = Auth::user()->patient->id;

        if ($appointment->is_accepted == 'pending' && $appointment->patient_id == $patientId) {
            return response()->json(['status' => 'error', 'message' => 'Appointment is already requested'], 400);
        }

        if ($appointment->is_accepted != 'accepted') {
            $newAppointment = $appointment->replicate(); 
            $newAppointment->patient_id = $patientId;  
            $newAppointment->is_accepted = 'pending'; 
            $newAppointment->save();

            return response()->json([
                'status' => 'success',
                'message' => 'Appointment requested successfully! A new appointment has been created.'
            ]);
        }

        // التحقق من وجود تعارض في المواعيد
        $conflict = Appointment::where('patient_id', $patientId)
            ->where('date', $appointment->date)
            ->where('is_accepted', 'accepted')  // التأكد من أن الموعد مقبول
            ->where(function ($query) use ($appointment) {
                $query->whereBetween('start_time', [$appointment->start_time, $appointment->end_time])
                    ->orWhereBetween('end_time', [$appointment->start_time, $appointment->end_time]);
            })
            ->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'You already have an appointment at this time. Please choose a different time slot.'
            ], 400);
        }

        $appointment->patient_id = $patientId;
        $appointment->is_accepted = 'pending';
        $appointment->save();

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment requested successfully!'
        ]);
    }

    public function myAppointments()
    {
        if (!Gate::allows('manage-their-schedule')) {
            abort(403, 'Unauthorized action.');
        }

        $patientId = Auth::user()->patient->id;
        $appointments = Appointment::where('patient_id', $patientId)->get();

        return response()->json($appointments);
    }

    public function cancelAppointment($id)
    {
        if (!Gate::allows('manage-their-schedulee')) {
            abort(403, 'Unauthorized action.');
        }
        $appointment = Appointment::findOrFail($id);

        if ($appointment->patient_id !== Auth::user()->patient->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if (Carbon::parse($appointment->date . ' ' . $appointment->time)->isPast()) {
            return response()->json(['message' => 'Cannot cancel a past appointment'], 400);
        }
        $appointment->patient_id = null;
        $appointment->is_accepted = null;
        $appointment->status = 'Available';

        $appointment->save();


        return response()->json(['message' => 'Appointment canceled successfully!'], 200);
    }

    public function getSpecializations()
    {
        if (!Gate::allows('can-rate')) {
            abort(403, 'Unauthorized action.');
        }
        $specializations = Doctor::select('major')
            ->distinct()
            ->get()
            ->map(function ($specialization) {
                $specialization->doctor_count = Doctor::where('major', $specialization->major)->count();
                return $specialization;
            });

        return response()->json([
            'specializations' => $specializations
        ], 200);
    }
    public function getDoctorsBySpecialization($specialization)
    {
        if (!Gate::allows('can-rate')) {
            abort(403, 'Unauthorized action.');
        }
        $doctors = Doctor::where('major', $specialization)->get();

        return response()->json([
            'doctors' => $doctors
        ], 200);
    }
    public function getDoctorAvailabilityByDay($doctorId, Request $request)
    {
        if (!Gate::allows('can-rate')) {
            abort(403, 'Unauthorized action.');
        }
        $date = $request->query('date');
        if (!$date || !\Carbon\Carbon::hasFormat($date, 'Y-m-d')) {
            return response()->json(['error' => 'Invalid date format. Use YYYY-MM-DD'], 400);
        }

        $appointments = Appointment::where('date', $date)
            ->where('status', 'Available')
            ->get();

        return response()->json($appointments);
    }
}
