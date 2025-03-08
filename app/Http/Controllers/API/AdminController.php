<?php

namespace App\Http\Controllers\API;

use Exception;
use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Mail;
use App\Mail\DoctorRequestStatusMail;
use App\Http\Controllers\FCMController;

class AdminController extends Controller
{
    public function getDoctorRequests(Request $request)
    {
        if (!Gate::allows('manage-doctor-requests')) {
            abort(403, 'Unauthorized action.');
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

    public function approveDoctorRequest(Request $request, $id, FCMController $fcmController): JsonResponse
    {
        if (!Gate::allows('manage-doctor-requests')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $doctorRequest = DoctorRequest::findOrFail($id);

        if ($doctorRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed!'], 400);
        }

        DB::beginTransaction();
        try {
            if (User::where('email', $doctorRequest->email)->exists()) {
                return response()->json(['error' => 'User with this email already exists.'], 409);
            }

            $user = User::create([
                'username' => strtolower($doctorRequest->first_name . " " . $doctorRequest->last_name),
                'email' => $doctorRequest->email,
                'password' => $doctorRequest->password,
                'role' => 'doctor',
                'fcm_token' => $request->token, //
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

            $doctorRequest->update(['status' => 'approved']);

            DB::commit();

            $deviceToken =   "aaaa"; //$user->fcm_token; // Using the newly created user’s FCM token
            $title = "Your request has been approved";
            $body = "Congratulations! you are a doctor in MEDSUPPORTGAZA";

            $notifyStatus = $fcmController->sendNotification($request, $deviceToken, $title, $body);
            Mail::to($user->email)->send(new DoctorRequestStatusMail($doctor, 'accepted'));

            return response()->json([
                'message' => 'Doctor approved successfully!',
                'notification_status' => $notifyStatus,
            ], 201);
        } catch (Exception $e) {
            DB::rollBack();
            return response()->json([
                'error' => 'Something went wrong while approving the doctor request.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
    public function rejectDoctorRequest(Request $request, FCMController $fcmController, $id): JsonResponse
    {
        if (!Gate::allows('manage-doctor-requests')) {
            return response()->json(['error' => 'Unauthorized action.'], 403);
        }

        $doctorRequest = DoctorRequest::findOrFail($id);
        if ($doctorRequest->status !== 'pending') {
            return response()->json(['message' => 'Request already processed!'], 400);
        }
        DB::beginTransaction();
        try {
            // $doctorRequest->update(['status' => 'rejected']);
            DB::commit();

            $deviceToken = $request->token ?? null;
            if ($deviceToken) {
                $title = "Your request has been rejected";
                $body = "Sorry, your request has been rejected \n Good Luck !";
                $notifyStatus = $fcmController->sendNotification($request, $deviceToken, $title, $body);
            } else {
                $notifyStatus = 'No FCM token provided';
            }

            return response()->json([
                'message' => 'Doctor request rejected successfully!',
                'notification_status' => $notifyStatus,
            ], 200);
        } catch (Exception $e) {
            DB::rollBack();
            Mail::to($doctorRequest->email)->send(new DoctorRequestStatusMail($doctorRequest, 'rejected'));

            return response()->json([
                'error' => 'Something went wrong while rejecting the doctor request.',
                'details' => $e->getMessage(),
            ], 500);
        }
    }
}
