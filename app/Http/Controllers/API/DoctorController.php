<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;
use Illuminate\Routing\Controllers\Middleware;

class DoctorController extends Controller
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except:['register'])
        ];
    }
    public function register(Request $request)
{
    $validatedData = $request->validate([
        'first_name' => 'required|string|max:255',
        'last_name' => 'required|string|max:255',
        'email' => 'required|email|unique:users',
        'license_number' => 'required|string|unique:doctor_requests',
        'major' => 'required|string|max:255',
        'phone_number' => 'required',
        'country' => 'required|string',
        'image' => 'nullable|string',

        // 'image' => 'nullable|image|mimes:jpeg,png,jpg,gif,svg|max:2048',

    ]);
    if ($request->hasFile('image')) {
        $image = $request->file('image');
        $imageName = time() . '.' . $image->getClientOriginalExtension();
        $image->storeAs('public/image', $imageName);
    } else {
        $imageName = null; // إذا لم يتم تحميل صورة
    }

    // حفظ الطلب في جدول doctor_requests
    DoctorRequest::create([
        'first_name' => $validatedData['first_name'],
        'last_name' => $validatedData['last_name'],
        'email' => $validatedData['email'],
        'license_number' => $validatedData['license_number'],
        'major' => $validatedData['major'],
        'country' => $validatedData['country'],
        'status' => 'pending', 
        'phone_number' => $validatedData['phone_number'],
        'image' => $imageName, 

    ]);

    return response()->json(['message' => 'Registration request submitted successfully!'], 201);
}

// public function login(Request $request)
// {
//     $validatedData = $request->validate([
//         'email' => 'required|email',
//         'password' => 'required',
//     ]);

//     $user = User::where('email', $validatedData['email'])->first();

//     if (!$user || !Hash::check($validatedData['password'], $user->password)) {
//         return response()->json(['message' => 'Invalid credentials'], 401);
//     }

//     // التحقق من دور المستخدم
//     if ($user->role !== 'doctor') {
//         return response()->json(['message' => 'Access denied. Only doctors can login here.'], 403);
//     }

//     // إنشاء توكن جديد
//     $token = $user->createToken('doctor-token')->plainTextToken;

//     return response()->json([
//         'message' => 'Login successful',
//         'token' => $token,
//         'user' => $user,
//     ]);
// }

public function login(Request $request)
{
    $validatedData = $request->validate([
        'email' => 'required|email',
        'password' => 'required|min:6',
    ]);

    // البحث عن المستخدم بناءً على الإيميل
    $user = User::where('email', $validatedData['email'])->first();

    // إذا المستخدم غير موجود أو كلمة المرور خاطئة
    if (!$user || !Hash::check($validatedData['password'], $user->password)) {
        return response()->json(['message' => 'Invalid credentials'], 401);
    }

    // إنشاء توكن مؤقت
    $token = $user->createToken('api_token')->plainTextToken;

    return response()->json([
        'message' => 'Login successful',
        'token' => $token,
        'user' => $user,
    ]);
}

/**
     * إضافة موعد جديد
     */
    public function addSchedule(Request $request)
    {
        $validated = $request->validate([
            'date' => 'required|date',
            'period' => 'required|string|in:صباحية,مسائية',
            'start_time' => 'required|date_format:H:i',
            'end_time' => 'required|date_format:H:i|after:start_time',
        ]);
        
        $validated['doctor_id'] = Auth::user()->doctor->id; // ربط الموعد بالطبيب

        $validated['status'] = 'Available'; // الموعد متاح افتراضيًا

        $appointment = Appointment::create($validated);

        return response()->json([
            'message' => 'Schedule added successfully!',
            'appointment' => $appointment
        ]);
    }

    /**
     * عرض مواعيد الطبيب
     */
    public function myAppointments()
    {
        $doctorId = auth::user()->doctor->id; // جلب الطبيب المرتبط بالمستخدم

        $appointments = Appointment::where('doctor_id', $doctorId)->get();

        return response()->json($appointments);
    }

    /**
     * حذف موعد متاح
     */
    public function deleteAppointment($id)
    {
        $appointment = Appointment::findOrFail($id);

        if ($appointment->doctor_id !== auth::user()->doctor->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $appointment->delete();

        return response()->json(['message' => 'Appointment deleted successfully!']);
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
        //
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
}
