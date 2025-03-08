<?php

namespace App\Http\Controllers;

use App\Mail\SendOtpMail;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        $user = User::where('email', $request->email)->first();

        if ($user && Hash::check($request->password, $user->password)) {
            Auth::login($user);
            $token = $user->createToken($user->username)->plainTextToken;
            if ($user->role === 'patient') {
                $patient = $user->patient;
                return response()->json([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'patient' => [
                        'id' => $patient->id,
                        'user_id' => $patient->user_id,
                        'first_name' => $patient->first_name,
                        'last_name' => $patient->last_name,
                        'email' => $user->email, // الإيميل داخل المريض
                        'age' => $patient->age,
                        'gender' => $patient->gender,
                        'phone_number' => $patient->phone_number,
                        'address' => $patient->address,
                    ],
                ],200);
            }

            if ($user->role === 'doctor') {
                $doctor = $user->doctor;
                return response()->json([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'doctor' => [
                        'id' => $doctor->id,
                        'user_id' => $doctor->user_id,
                        'first_name' => $doctor->first_name,
                        'last_name' => $doctor->last_name,
                        'email' => $user->email,
                        'major' => $doctor->major,
                        'country' => $doctor->country,
                        'phone_number'    => $doctor->phone_number,
                        'average_rating'  => $doctor->average_rating,
                        'image'           => asset("storage/{$doctor->image}"),
                        'certificate'     => asset("storage/{$doctor->certificate}"),
                        'gender'          => $doctor->gender,
                    ],
                ],200);
            }
            if ($user->role === 'admin') {
                $admin = $user->admin;
                return response()->json([
                    'message' => 'Login successful.',
                    'token' => $token,
                    'admin' => [
                        'id' => $admin->id,
                        'user_id' => $admin->user_id,
                        'first_name' => $admin->first_name,
                        'last_name' => $admin->last_name,
                        'email' => $user->email,
                        'number' => $admin->number,
                        'job_title' => $admin->job_title,
                    ],

                ] ,200 );
            }

            return response()->json([
                'message' => 'Login successful.',
                'token' => $token,
                'user' => $user,
            ], 200);
        }

        return response()->json([
            'code' => 401,
            'message' => 'Invalid email or password.',
        ], 401);
    }


    // public function login(Request $request)
    // {
    //     $validated = $request->validate([
    //         'email' => 'required|exists:users,email',
    //         'password' => 'required',
    //     ]);
    //     $user = User::where('email', $request->email)->first();
    //     // return $user->password . "  A   "  . Hash::make($request->password);
    //     if ($user && Hash::check($request->password, $user->password)) {
    // return "AAAAAAAAA";
    //         Auth::login($user);

    //         $token = $user->createToken($user->username)->plainTextToken;

    //         return response()->json([
    //             'message' => 'Login successful.',
    //             'token' => $token,
    //             'user' => $user,
    //         ], 200);
    //     }

    //     return response()->json([
    //         'code' => 302,
    //         'message' => 'Invalid email or password.',
    //     ], 302);
    // }

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

        // Mail::raw("Your password reset code is: $otp. It will expire in 15 minutes.", function ($message) use ($user) {
        //     $message->to($this->$user->email)
        //         ->subject('Password Reset Code');
        // });
Mail::to($user->email)->send(new SendOtpMail($otp));
        return response()->json(['message' => 'The code has been sent to your email.']);
    }
    public function verifyOtp(Request $request)
    {
        $request->validate([
            'email' => 'required|email|exists:users,email',
            'otp' => 'required|numeric',
        ]);

        $user = User::where('email', $request->email)->first();

        if (!$user || intval($user->otp_code) !== intval($request->otp)) {
            return response()->json(['message' => 'Invalid OTP'], 400);
        }
        if (!$user || intval($user->otp_code) !== intval($request->otp) || now()->greaterThan($user->otp_expires_at)) {
            return response()->json(['message' => 'Invalid or expired OTP'], 400);
        }
        
        $user->otp_code = null;
        $user->otp_expires_at = null;
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


