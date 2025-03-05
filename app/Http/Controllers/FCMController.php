<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FCMController extends Controller
{
    public function sendNotification(Request $request, $deviceToken, $title, $body)
    {
        $messaging = app('firebase.messaging');

        $deviceToken = 'paramaters';

        $message = [
            'token' => $deviceToken,
            'notification' => [
                'title' => $title,
                'body' => $body,
            ],
            'data' => [
                'doc_id' => $request->input('doctor_id'), // Add any custom data payload here
            ],
        ];

        try {
            $messaging->send($message);

            return response()->json(['success' => true, 'message' => 'Notification sent successfully!']);
        } catch (\Throwable $e) {
            return response()->json(['error' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
