<?php

namespace App\Http\Controllers\API;

use App\Models\User;
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
            new Middleware('auth:sanctum', except:['register'])
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
 

// public function login(Request $request)
// {
//     $validatedData = $request->validate([
//         'email' => 'required|email',
//         'password' => 'required|min:6',
//     ]);

//     $user = User::where('email', $validatedData['email'])->first();

//     if (!$user || !Hash::check($validatedData['password'], $user->password)) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     $token = $user->createToken('api_token')->plainTextToken;

//     return response()->json([
//         'message' => 'Login successful',
//         'token' => $token,
//         'user' => $user,
//     ]);
// }

/**     */
    public function addSchedule(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'period' => 'required|string|in:صباحية,مسائية',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        
        $validated['doctor_id'] = Auth::user()->doctor->id; 

        $validated['status'] = 'Available'; 

        $appointment = Appointment::create($validated);

        return response()->json([
            'message' => 'Schedule added successfully!',
            'appointment' => $appointment
        ]);
    }

    /**
     */
    public function myAppointments()
    {
        $doctorId = auth::user()->doctor->id; 

        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        return response()->json($appointments);
    }

    /**
     * 
     */
    public function deleteAppointment($id)
    {
       
            $appointment = Appointment::findOrFail($id);
        
            // تحقق من أن المستخدم الحالي لديه دور "Doctor"
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
    public function update(Request $request, string $id)
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
        'specialization' => 'nullable|string', 
    ]);

    $query = Doctor::query();

    if (!empty($validatedData['name'])) {
        $query->whereRaw("CONCAT(first_name, ' ', last_name) LIKE ?", ['%' . $validatedData['name'] . '%']);
    }

    // البحث حسب التخصص
    if (!empty($validatedData['major'])) {
        $query->where('major', 'LIKE', '%' . $validatedData['major'] . '%');
    }

    $doctors = $query->get();

    return response()->json($doctors);
}

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
