<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use App\Models\Doctor;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Routing\Controllers\Middleware;

class DoctorController extends Controller
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['register'])
        ];
    }
    public function profile(Request $request)
    {
        $doctor = $request->user()->doctor;
        return response()->json($doctor);
    }
    public function register(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'required|string|max:255',
            'last_name' => 'required|string|max:255',
            'email' => 'required|email',
            'password' => 'required|string|min:8|confirmed',
            'country' => 'required|string|max:255',
            'phone_number' => 'required|string|max:20',
            'gender' => 'required|in:male,female',
            'major' => 'required|string|max:255',
            'certificate' => 'required|file|mimes:pdf,jpeg,png,jpg',
        ]);

        DB::beginTransaction();
        try {
            $existingRequest = DoctorRequest::where('email', $validatedData['email'])->first();

            if ($existingRequest && $existingRequest->status === 'rejected') {
                $existingRequest->delete();
            }

            if ($request->hasFile('certificate')) {
                $certificate = $request->file('certificate');
                $certificateName = time() . '.' . $certificate->getClientOriginalExtension();
                $certificate->storeAs('public/doctors/certificates', $certificateName);
            } else {
                $certificateName = null;
            }

            DoctorRequest::create([
                'first_name' => $validatedData['first_name'],
                'last_name' => $validatedData['last_name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'country' => $validatedData['country'],
                'phone_number' => $validatedData['phone_number'],
                'gender' => $validatedData['gender'],
                'major' => $validatedData['major'],
                'certificate' => $certificateName,
                'status' => 'pending',
            ]);

            DB::commit();

            return response()->json(['message' => 'Registration request submitted successfully!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'An error occurred. Please try again later.'], 500);
        }
    }

    public function addSchedule(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'period' => 'nullable|string|in:AM,PM', 
            'start_time' => 'required|string',
            'end_time' => 'required|string',
        ]);

        // Convert and normalize time inputs
        $validated['start_time'] = $this->normalizeTimeTo12Hour($validated['start_time'], $validated['period']);
        $validated['end_time'] = $this->normalizeTimeTo12Hour($validated['end_time'], $validated['period']);

        if (!$validated['start_time'] || !$validated['end_time']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid time format. Please enter a valid time (e.g., "10:30 AM" or "10 PM").'
            ], 422);
        }

        // Extract AM/PM from the converted time
        $validated['period'] = Carbon::parse($validated['start_time'])->format('A'); 
        $validated['start_time'] = Carbon::parse($validated['start_time'])->format('h:i'); 
        $validated['end_time'] = Carbon::parse($validated['end_time'])->format('h:i'); 
        if (Carbon::parse($validated['start_time'] . ' ' . $validated['period'])->greaterThanOrEqualTo(Carbon::parse($validated['end_time'] . ' ' . $validated['period']))) {
            return response()->json([
                'status' => 'error',
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        // Ensure appointment is not in the past
        if (Carbon::parse($validated['date'] . ' ' . $validated['start_time'] . ' ' . $validated['period'])->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected appointment time has already passed. Please choose a future time.'
            ], 422);
        }

        $doctorId = Auth::user()->doctor->id;

        $conflict = Appointment::where('doctor_id', $doctorId)
            ->where('date', $validated['date'])
            ->where('period', $validated['period']) 
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                });
            })->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected time slot is unavailable due to a scheduling conflict. Please choose a different time within the same period (AM/PM).'
            ], 422);
        }

        $validated['doctor_id'] = $doctorId;
        $validated['status'] = 'Available';

        $appointment = Appointment::create($validated);

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule added successfully!',
            'appointment' => $appointment
        ]);
    }

    /**
     * Normalize time input and convert it to 12-hour format
     */
    private function normalizeTimeTo12Hour($time, $period = null)
    {
        if (preg_match('/^\d{1,2}$/', $time)) {
            $time .= ':00';
        }

        try {
            $carbonTime = $period
                ? Carbon::parse("$time $period")
                : Carbon::parse($time);

            return $carbonTime->format('h:i A');
        } catch (\Exception $e) {
            return null; 
        }
    }


    public function myAppointments(Request $request)
    {
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
            'appointments' => $appointments
        ]);
    }


    /**
     * 
     */
    public function deleteAppointment($id)
    {

        $appointment = Appointment::findOrFail($id);

        if (auth::user()->role !== 'doctor') {
            return response()->json(['message' => 'Unauthorized. User is not a doctor.'], 403);
        }

        if ($appointment->doctor_id !== auth::user()->doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        // حذف الموعد
        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully!']);
    }
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $doctors = Doctor::all();
        return response()->json($doctors);
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
        $doctor = Doctor::find($id);
        if (!$doctor) {
            return response()->json(['message' => 'Doctor not found'], 404);
        }
        return response()->json($doctor);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request)
    {
        $validatedData = $request->validate([
            'first_name' => 'nullable|string|max:255',
            'last_name' => 'nullable|string|max:255',
            'major' => 'nullable|string|max:255',
            'country' => 'nullable|string|max:255',
            'phone_number' => 'nullable|string|max:20',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'certificate' => 'nullable|file|mimes:pdf,jpeg,png,jpg|max:2048',
            'gender' => 'nullable|in:male,female',
        ]);

        $doctor = Doctor::where('user_id', Auth::id())->first();

        if ($request->hasFile('image')) {
            $image = $request->file('image');
            $imageName = time() . '_image.' . $image->getClientOriginalExtension();
            $image->storeAs('public/doctors/images', $imageName);
            $validatedData['image'] = $imageName;

            if ($doctor->image) {
                Storage::delete('public/doctors/images/' . $doctor->image);
            }
        }

        if ($request->hasFile('certificate')) {
            $certificate = $request->file('certificate');
            $certificateName = time() . '_certificate.' . $certificate->getClientOriginalExtension();
            $certificate->storeAs('public/doctors/certificates', $certificateName);
            $validatedData['certificate'] = $certificateName;

            if ($doctor->certificate) {
                Storage::delete('public/doctors/certificates/' . $doctor->certificate);
            }
        }

        $doctor->update($validatedData);

        return response()->json(['message' => 'Doctor updated successfully!', 'doctor' => $doctor], 200);
    }

    public function searchDoctors(Request $request)
{
    $validatedData = $request->validate([
        'name' => 'nullable|string',
        'major' => 'nullable|string',
    ]);

    $query = Doctor::query();

    if (!empty($validatedData['name'])) {
        $query->whereRaw("LOWER(CONCAT(first_name, ' ', last_name)) LIKE LOWER(?)", ['%' . strtolower($validatedData['name']) . '%']);
    }

    if (!empty($validatedData['major'])) {
        $query->whereRaw("LOWER(major) LIKE LOWER(?)", ['%' . strtolower($validatedData['major']) . '%']);
    }

    $doctors = $query->get();

    if ($doctors->isEmpty()) {
        return response()->json(['message' => 'No doctors found'], 404);
    }

    return response()->json([
        'status' => 'success',
        'doctors' => $doctors
    ]);
}


    
    public function destroy(string $id)
    {
        //
    }

    public function updateSchedule(Request $request, $appointmentId)
    {
        $validated = $request->validate([
            'date' => 'required|date|after_or_equal:today',
            'period' => 'nullable|string|in:AM,PM',
            'start_time' => 'required|string',
            'end_time' => 'required|string',
        ]);

        $appointment = Appointment::findOrFail($appointmentId);

        $validated['start_time'] = $this->normalizeTimeTo12Hour($validated['start_time'], $validated['period']);
        $validated['end_time'] = $this->normalizeTimeTo12Hour($validated['end_time'], $validated['period']);

        if (!$validated['start_time'] || !$validated['end_time']) {
            return response()->json([
                'status' => 'error',
                'message' => 'Invalid time format. Please enter a valid time (e.g., "10:30 AM" or "10 PM").'
            ], 422);
        }

        $validated['period'] = Carbon::parse($validated['start_time'])->format('A'); 
        $validated['start_time'] = Carbon::parse($validated['start_time'])->format('h:i'); 
        $validated['end_time'] = Carbon::parse($validated['end_time'])->format('h:i'); 

        if (Carbon::parse($validated['start_time'] . ' ' . $validated['period'])->greaterThanOrEqualTo(Carbon::parse($validated['end_time'] . ' ' . $validated['period']))) {
            return response()->json([
                'status' => 'error',
                'message' => 'End time must be later than start time.'
            ], 422);
        }

        if (Carbon::parse($validated['date'] . ' ' . $validated['start_time'] . ' ' . $validated['period'])->isPast()) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected appointment time has already passed. Please choose a future time.'
            ], 422);
        }

        $doctorId = Auth::user()->doctor->id;

        $conflict = Appointment::where('doctor_id', $doctorId)
            ->where('date', $validated['date'])
            ->where('period', $validated['period']) 
            ->where(function ($query) use ($validated) {
                $query->where(function ($q) use ($validated) {
                    $q->where('start_time', '<', $validated['end_time'])
                        ->where('end_time', '>', $validated['start_time']);
                });
            })
            ->where('id', '!=', $appointment->id) 
            ->exists();

        if ($conflict) {
            return response()->json([
                'status' => 'error',
                'message' => 'The selected time slot is unavailable due to a scheduling conflict. Please choose a different time within the same period (AM/PM).'
            ], 422);
        }

        $appointment->update([
            'date' => $validated['date'],
            'start_time' => $validated['start_time'],
            'end_time' => $validated['end_time'],
            'period' => $validated['period'],
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Appointment updated successfully!',
            'appointment' => $appointment
        ]);
    }
}
