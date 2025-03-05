<?php

namespace App\Http\Controllers\API;

use Carbon\Carbon;
use Pusher\Pusher;
use App\Models\Message;
use App\Models\Appointment;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;

class ChatController extends Controller
{
    public function sendMessage(Request $request)
    {
        $request->validate([
            'receiver_id' => 'required|exists:users,id',
            'appointment_id' => 'required|exists:appointments,id',
            'message' => 'required|string',
        ]);

        $user = Auth::user();
        if ($user->role == 'doctor') {
            $userId = $user->doctor->id;
        } elseif ($user->role == 'patient') {
            $userId = $user->patient->id;
        } else {
            return response()->json(['error' => 'Invalid user role.'], 403);
        }

        $receiverId = $request->receiver_id;
        $appointmentId = $request->appointment_id;
        $now = Carbon::now()->format('H:i');

        $appointment = Appointment::where('id', $appointmentId)
            ->where(function ($query) use ($userId, $receiverId) {
                $query->where('patient_id', $userId)->where('doctor_id', $receiverId);
            })->orWhere(function ($query) use ($userId, $receiverId) {
                $query->where('doctor_id', $userId)->where('patient_id', $receiverId);
            })->first();

        if (!$appointment) {
            return response()->json(['error' => 'No active appointment found between you and the receiver.'], 403);
        }

        $startTime = Carbon::parse($appointment->start_time)->format('H:i');
        $endTime = Carbon::parse($appointment->end_time)->format('H:i');

        if (!($now >= $startTime && $now <= $endTime)) {
            return response()->json(['error' => 'You can only send messages during the appointment time.'], 403);
        }

        $message = Message::create([
            'sender_id' => $userId,
            'receiver_id' => $receiverId,
            'appointment_id' => $appointmentId,
            'message' => $request->message,
            'is_read' => false,
        ]);

        $this->sendToPusher($message);

        return response()->json(['message' => $message], 201);
    }

    private function sendToPusher($message)
    {
        $options = [
            'cluster' => env('PUSHER_APP_CLUSTER'),
            'useTLS' => true,
        ];

        $pusher = new Pusher(
            env('PUSHER_APP_KEY'),
            env('PUSHER_APP_SECRET'),
            env('PUSHER_APP_ID'),
            $options
        );

        $data = [
            'id' => $message->id,
            'sender_id' => $message->sender_id,
            'receiver_id' => $message->receiver_id,
            'message' => $message->message,
            'created_at' => $message->created_at->toDateTimeString(),
        ];

        $pusher->trigger("chat.{$message->receiver_id}", 'new-message', $data);
    }

    public function getMessages($appointmentId)
    {
        $user = Auth::user();
        if ($user->role == 'doctor') {
            $userId = $user->doctor->id;
        } elseif ($user->role == 'patient') {
            $userId = $user->patient->id;
        } else {
            return response()->json(['error' => 'Invalid user role.'], 403);
        }
    
        // 🔥 2️⃣ التحقق مما إذا كان المستخدم جزءًا من الموعد
        $appointment = Appointment::where('id', $appointmentId)
            ->where(function ($query) use ($userId) {
                $query->where('patient_id', $userId)
                      ->orWhere('doctor_id', $userId);
            })->first();
    
        if (!$appointment) {
            return response()->json(['error' => 'You do not have permission to view messages for this appointment.'], 403);
        }

        $messages = Message::where('appointment_id', $appointmentId)
            ->orderBy('created_at', 'asc')
            ->get();
            Message::where('appointment_id', $appointmentId)
            ->where('receiver_id', $userId)
            ->where('is_read', false)
            ->update(['is_read' => true]);
            
        return response()->json(['messages' => $messages]);
    }
    public function download($filename) {
        if (strpos($filename, '..') !== false) {
            abort(403, "Access Denied");
        }
            $path = storage_path("app/public/chat_files/{$filename}");
    
        if (!file_exists($path)) {
            abort(404, "File not found");
        }
            return response()->download($path);
    }
}
