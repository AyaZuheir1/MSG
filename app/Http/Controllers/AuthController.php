<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Mail\SendOtpMail;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user); // Logs in the user
            $token = $user->createToken($user->username)->plainTextToken; // Generate token

            return response()->json([
                'user' => $user,
                'token' => $token,
            ], 200);
        }

        return response()->json([
            'code' => 302,
            'message' => 'Invalid email or password.',
        ], 302);
    }

    public function logout(Request $request)
    {
        $request->user()->tokens()->delete();
        return [
            'msg' => 'you are logged out',
        ];
    }
   

    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'البريد الإلكتروني غير موجود.'], 404);
        }

        $otp = rand(1000, 9999);
        $expiryTime = now()->addMinutes(15); 

        $user->otp_code = $otp;
        $user->otp_expires_at = $expiryTime;
        $user->save();

        Mail::raw("Your password reset code is: $otp. It will expire in 15 minutes.", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Reset Code');
        });

        return response()->json(['message' => 'تم إرسال رمز التحقق إلى بريدك الإلكتروني.']);
    }


    public function verifyOtp(Request $request)
    {
        $request->validate([
            'otp_code' => 'required|integer',
        ]);

        $user = User::where('otp_code', $request->otp_code)->first();
        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['error' => 'Invalid reset code.'], 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['error' => 'Reset code has expired.'], 400);
        }

        return response()->json(['message' => 'OTP code verified successfully. You can now reset your password.']);
    }

    public function resetPassword(Request $request)
    {
        $request->validate([
            'new_password' => 'required|min:8|confirmed', 
        ]);

        $user = User::where('otp_code', $request->otp_code)->first();
        if (!$user || $user->otp_code !== $request->otp_code) {
            return response()->json(['error' => 'Invalid reset code.'], 400);
        }

        if (now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['error' => 'Reset code has expired.'], 400);
        }

        $user->password = Hash::make($request->new_password);
        $user->otp_code = null;  
        $user->otp_expires_at = null; 
        $user->save();

        return response()->json(['message' => 'Password has been reset successfully.']);
    }
    public function resendOtp(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }

    // توليد OTP جديد
    $otp = rand(1000, 9999); // رمز مكون من 4 أرقام
    $user->otp = $otp;
    $user->otp_expires_at = now()->addMinutes(5); // مدة انتهاء الصلاحية
    $user->save();

    // إرسال OTP للمستخدم (البريد الإلكتروني أو الرسائل النصية)
    // Mail::to($user->email)->send(new SendOtpMail($otp));
    Mail::raw("Your password reset code is: $otp. It will expire in 15 minutes.", function ($message) use ($user) {
        $message->to($user->email)
            ->subject('Password Reset Code');
    });
    return response()->json(['message' => 'OTP sent successfully']);
}

}
