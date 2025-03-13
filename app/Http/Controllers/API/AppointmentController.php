<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
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
            'status' => 'Available',
            'period' => $request->period,
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
        $appointments = Appointment::where('doctor_id', $doctorId)
            ->when($status !== 'all', function ($query) use ($status) {
                return $query->where('status', $status);
            })
            ->orderBy('date', 'asc')
            ->orderBy('start_time', 'asc')
            ->get();

        return response()->json([
            'status' => 'success',
            'appointments' => [
                'id' => $appointments->id,
                'doctor_id' => $appointments->doctor_id,
                'date' => $appointments->date,
                'start_time' => Carbon::createFromFormat('H:i', $appointments->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $appointments->end_time)->format('h:i A'),
                'status' => $appointments->status,
                'is_accepted' => $appointments->is_accepted,
            ]
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
        $pendingAppointments = Appointment::where('is_accepted', 'pending')->get();


        return response()->json([
            'status' => 'success',
            'appointments' => [
                'id' => $pendingAppointments->id,
                'doctor_id' => $pendingAppointments->doctor_id,
                'date' => $pendingAppointments->date,
                'start_time' => Carbon::createFromFormat('H:i', $pendingAppointments->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $pendingAppointments->end_time)->format('h:i A'),
                'status' => $pendingAppointments->status,
                'is_accepted' => $pendingAppointments->is_accepted,
            ]
        ]);
    }
    public function acceptAppointment($appointmentId)
    {
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
                'start_time' => Carbon::createFromFormat('H:i', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ]);
    }
    public function rejectAppointment($appointmentId)
    {
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
                'start_time' => Carbon::createFromFormat('H:i', $appointment->start_time)->format('h:i A'),
                'end_time' => Carbon::createFromFormat('H:i', $appointment->end_time)->format('h:i A'),
                'status' => $appointment->status,
                'is_accepted' => $appointment->is_accepted,
            ]
        ]);
    }
}
