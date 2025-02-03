<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Doctor;
use App\Models\Article;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use Illuminate\Support\Carbon;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FCMController;
use App\Notifications\ReviewDoctorRequestNotification;
use Google\Cloud\Storage\Connection\Rest;

class AdminController extends Controller
{
    public function getDoctorRequests(Request $request)
    {
        // تحقق إذا تم تحديد حالة معينة لتصفية الطلبات
        $status = $request->query('status'); // يتم قراءة الحالة من بارامتر الكويري (query string)

        if ($status) {
            // التحقق من أن الحالة صحيحة
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                return response()->json(['message' => 'Invalid status'], 400);
            }

            // جلب الطلبات بناءً على الحالة المحددة
            $requests = DoctorRequest::where('status', $status)->get();
        } else {
            // جلب جميع الطلبات بدون تصفية
            $requests = DoctorRequest::all();
        }

        return response()->json($requests);
    }

    public function approveDoctorRequest(Request $request, $id)
    {
        $doctorRequest = DoctorRequest::findOrFail($id);
        $doctor = null;
        if ($doctorRequest->status === 'pending') {
            $user = User::create([
                'username' => strtolower($doctorRequest->first_name . $doctorRequest->last_name),
                'email' => $doctorRequest->email,
                'password' => bcrypt('defaultpassword'),
                'role' => 'doctor',
            ]);

            $doctor = Doctor::create([
                'user_id' => $user->id,
                'first_name' => $doctorRequest->first_name,
                'last_name' => $doctorRequest->last_name,
                'license_number' => $doctorRequest->license_number,
                'major' => $doctorRequest->major,
                'phone_number' => $doctorRequest->phone_number,
                'country' => $doctorRequest->country,
                'image' => $doctorRequest->image,
            ]);

            // تحديث حالة الطلب
            $doctorRequest->update(['status' => 'approved']);

            $fcmController = new FCMController();

           $notifyStatus =  $fcmController->sendNotification($request);

            return response()->json(['message' => 'Doctor approved successfully!',
            'status' => $notifyStatus]);
        }

        return response()->json(['message' => 'Request already processed!'], 400);
    }

    public function rejectDoctorRequest($id)
    {
        $request = DoctorRequest::findOrFail($id);

        if ($request->status === 'pending') {
            $request->update(['status' => 'rejected']);
            return response()->json(['message' => 'Doctor request rejected successfully!']);
        }

        return response()->json(['message' => 'Request already processed!'], 400);
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
