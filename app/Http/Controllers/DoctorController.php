<?php

namespace App\Http\Controllers;

use App\Models\Doctor;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\DB;

class DoctorController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except:['register'])
        ];
    }
    public function register(Request $request)
{
    $validated = $request->validate([
        'first_name' => 'required|string',
        'last_name' => 'required|string',
        'email' => 'required|email|unique:users,email',
        'password' => 'required|string|confirmed',
        'major' => 'required|string',
        'license_number' => 'required|integer',
        'country' => 'required|string',
        'phone_number' => 'required|string',
        'bio' => 'nullable|string',
        'image' => 'nullable|image|mimes:jpg,jpeg,png|max:2048',
    ]);

    try {
        DB::beginTransaction();

        $user = User::create([
            'username' => $request->first_name . ' ' . $request->last_name,
            'email' => $request->email,
            'password' => bcrypt($request->password),
            'role' => 'doctor',
        ]);

        $doctor = Doctor::create([
            'user_id' => $user->id,
            'first_name' => $request->first_name,
            'last_name' => $request->last_name,
            'major' => $request->major,
            'license_number' => $request->license_number,
            'country' => $request->country,
            'phone_number' => $request->phone_number,
            'bio' => $request->bio,
        ]);

        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('public/images');
            $doctor->image = $imagePath;
            $doctor->save();
        }

        $token = $user->createToken($request->first_name)->plainTextToken;

        DB::commit();

        return response()->json([
            'user' => $user,
            'doctor' => $doctor,
            'token' => $token,
        ], 201);
    } catch (\Exception $e) {
        DB::rollBack();
        return response()->json(['error' => $e->getMessage()], 500);
    }
}
    /**
     * Display a listing of the resource.
     */
    public function index() {}

    public function store(Request $request) {}

    public function show($id)
    {
        $doctor =  Doctor::where('id', $id);
        return [
            'code' => 200,
            'message' => 'success',
            'doctor' => $doctor,
        ];
    }
    //  هناك سؤال 
    public function update(Request $request, $id)
    {
        $Validated = $request->validate([
            'first_name' => 'required | string',
            'last_name' => 'required | string',
            'email' => 'required | email',
            'password' => 'required |string',
            'password_confirmation' => 'required |string',
            'major' => 'required | string',
            'license_number' => 'required | integer',
            'country' => 'required |string',
            'phone_number' => 'required',
            'bio' => 'nullable | string',
            'image' => 'nullable',
        ]);
        DB::beginTransaction();

        DB::commit();
        // event to notify admin that the user has  changed their informations 
    }

    public function destroy($id)
    {
        Doctor::destroy($id);
        return [
            'code' => 200,
            'message' => 'Patient deleted Successfully',
        ];
    }

   
}