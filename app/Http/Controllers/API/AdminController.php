<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Http\Controllers\FCMController;
use App\Notifications\DoctorAccountActivate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

// use App\Http\Controllers\API\DoctorAccountActivate;

class AdminController extends Controller
{
    public function getDoctorRequests(Request $request)
    {
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $status = $request->query('status');
        if ($status) {
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                return response()->json(['message' => 'Invalid status'], 400);
            }

            $requests = DoctorRequest::where('status', $status)->get();
        } else {
            $requests = DoctorRequest::all();
        }

        return response()->json([
            'code' => 200,
            'message' => $requests,
        ], 200);
    }

    public function approveDoctorRequest(Request $request, $id)
    {
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $doctorRequest = DoctorRequest::findOrFail($id);
        $doctor = null;
        if ($doctorRequest->status === 'pending') {
            $user = User::create([
                'username' => strtolower($doctorRequest->first_name . " " . $doctorRequest->last_name),
                'email' => $doctorRequest->email,
                'password' => $doctorRequest->password,
                'role' => 'doctor',
                'fcm_token' => $request->token,
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
                'certificate' => $doctorRequest->certificate,
            ]);

            // تحديث حالة الطلب
            $doctorRequest->update(['status' => 'approved']);
            $fcmController = new FCMController();
            // return "Pending";
            $deviceToken = "1|V5JSddLZlMu7FaXrEaK9Hv3A8Hva59iPveSG7YkQ0542bb6f";
            $title = "Your request has been approved";
            $body = "Congratulations! YOU ARE A DOCTOR IN MEDSUPPORTGAZA";
            // return $body;
            $notifyStatus =  $fcmController->sendNotification($request, $deviceToken, $title, $body);
            // $doctor->notify(new DoctorAccountActivate());

            // $user->notify(new DoctorAccountActivate);
            return response()->json([
                'message' => 'Doctor approved successfully!',
                'status' => $notifyStatus,
            ]);
        }

        return response()->json(['message' => 'Request already processed!'], 400);
    }


    public function rejectDoctorRequest(Request $request, $id)
    {
        // return !($request->user()->role == 'admin');
        if(!(Auth::user()->role == 'admin')) {
            abort(403, 'You are not authorized.');
        }
        $doctorRequest = DoctorRequest::findOrFail($id);

        if ($doctorRequest->status === 'pending') {
            $doctorRequest->update(['status' => 'rejected']);

            $fcmController = new FCMController();
            // return "Pending";
            $deviceToken =$request->token;
            $title = "Your request has been rejected";
            $body = "Sorry, your request has been rejected";
            $notifyStatus =  $fcmController->sendNotification($request, $deviceToken, $title, $body);
            // return $body;

            return response()->json([
                'code' => 200,
                'message' => 'Doctor request rejected successfully!',
                'notify_status' =>$notifyStatus,
            ], 200);
        }

        return response()->json([
            'code' => 400,
            'message' => 'Request already processed!'
        ], 400);
    }
}
