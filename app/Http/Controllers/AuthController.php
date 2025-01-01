<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

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
}
