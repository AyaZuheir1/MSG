<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Session;

class AuthController extends Controller
{
    // public function selectRole(Request $request)
    // {
    //     $validatedData = $request->validate([
    //         'role' => 'required|in:doctor,patient',
    //     ]);

    //     $redirectPage = $validatedData['role'] === 'doctor' ? '/doctor/splash' : '/patient/splash';

    //     return response()->json([
    //         'message' => 'Role selected successfully!',
    //         'role' => $validatedData['role'],
    //         'redirect_to' => $redirectPage,
    //     ]);
    // }
    public function login(Request $request)
    {
        $validated = $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required',
        ]);
        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $token = $user->createToken($user->username)->plainTextToken;

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
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
        return response()->json([
            'code' => 200,
            'message' => 'YOU ARE LOGGED OUT!',
        ]);
    }


    public function forgotPassword(Request $request)
    {
        $request->validate(['email' => 'required|email|exists:users,email']);

        $user = User::where('email', $request->email)->first();
        if (!$user) {
            return response()->json(['error' => 'This email is not found '], 404);
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

        return response()->json(['message' => 'The code has been sent to your email.']);
    }

    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);
    
        $user = User::where('email', $request->email)->first();
    
        if (!$user || $user->otp_code !== $request->otp) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }
    
        $user->otp_code = null;
        $user->save();
    
        return response()->json([
            'message' => 'OTP verified successfully',
            'redirect_to' => '/api/reset-password', 
       ], 200);
    }
    


public function resetPassword(Request $request)
{
    $request->validate([
        'email' => 'required|email|exists:users,email',
        'password' => 'required|min:8|confirmed',
    ]);

    $user = User::where('email', $request->email)->first();

    if (!$user) {
        return response()->json(['message' => 'User not found'], 404);
    }
    $user->password = Hash::make($request->password);
    $user->save();

    return response()->json(['message' => 'Password reset successfully!'], 200);
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

        $otp = rand(1000, 9999);
        $user->otp = $otp;
        $user->otp_expires_at = now()->addMinutes(15);
        $user->save();

        // Mail::to($user->email)->send(new SendOtpMail($otp));
        Mail::raw("Your password reset code is: $otp. It will expire in 15 minutes.", function ($message) use ($user) {
            $message->to($user->email)
                ->subject('Password Reset Code');
        });
        return response()->json(['message' => 'OTP sent successfully']);
    }
}
