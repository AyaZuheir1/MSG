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
        $doctor =  Doctor::findOrFail($request->user()->id);
        return response()->json([
                'doctor' => $doctor
            ]
        );
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
// return $existingRequest;
            if ($existingRequest) {
                if ($existingRequest->status === 'rejected') {
                    $existingRequest->delete();
                } else {
                    return response()->json(['message' => 'A request with this email is already pending.'], 400);
                }
            }

            $certificatePath = null;
            if ($request->hasFile('certificate')) {
                $certificatePath = $request->file('certificate')->store('doctors/certificates', 'public');
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
                'certificate' => $certificatePath,
                'status' => 'pending',
            ]);

            DB::commit();
            return response()->json(['message' => 'Registration request submitted successfully!'], 201);
        } catch (\Exception $e) {
            DB::rollBack();

            // Remove stored file if an error occurs
            // if ($certificatePath) {
            //     Storage::disk('public')->delete($certificatePath);
            }
//WHY????????????????????????????????????????????????????/
return response()->json(['message' =>  $e->getMessage() ], 500);
//WHY????????????????????????????????????????????????????/
        }
    


    public function addSchedule(Request $request)
    {
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
            'status' => 'Available'
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Schedule added successfully!',
            'appointment' => $appointment
        ]);
    }




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




    public function updateSchedule(Request $request, $appointmentId)
    {
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
            'appointment' => $appointment
        ]);
    }
}
