<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class FCMController extends Controller
{
    public function sendNotification(Request $request,$deviceToken,$title,$body)
    {
        $messaging = app('firebase.messaging');
        
        $deviceToken = 'paramaters';
        // $deviceToken = $request->input('message.notification.token');
        // $title = $request->input('message.notification.title', 'Default Title');
        // $body = $request->input('message.notification.body', 'Default Body');
        
        
        // return $deviceToken ."Sa";
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
            return 'success' ;
            return response()->json(['success' => true, 'message' => 'Notification sent successfully!']);
        } catch (\Throwable $e) {
            // return 'fail';
            return response()->json(['error' => false, 'error' => $e->getMessage()], 500);
        }
    }
}
