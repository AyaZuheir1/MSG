<?php

namespace App\Http\Controllers;

<<<<<<< HEAD
use Illuminate\Http\Request;

class PatientController extends Controller
{
=======
use App\Http\Requests\PatientRequest;
use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Review;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class PatientController extends Controller implements HasMiddleware
{
    public static function middleware()
    {
        return [
            new Middleware('auth:sanctum', except: ['register'])
        ];
    }
    public function register(Request $request)
    {
        $Validated =  $request->validate([
            'first_name' => 'required|string',
            'last_name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|confirmed',
            'SSN' => 'required|string',
            'age' => 'required|integer',
            'gender' => 'required|in:male,female',
            'phone_number' => 'required|string',
            'address' => 'required|string',
        ]);
        try {
            DB::beginTransaction();
            $user = User::create([
                'username' => $request->first_name . ' ' . $request->last_name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
                'role' => 'patient',
            ]);
            $patient = Patient::create(
                [
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->first_name,
                    'SSN' => $request->SSN,
                    'age' => $request->age,
                    'gender' => $request->gender,
                    'phone_number' => $request->phone_number,
                    'address' => $request->address,
                ]
            );
            $token = $user->createToken($request->first_name)->plainTextToken;
            DB::commit();
            return response()->json([
                'user' => $user,
                'patient' => $patient,
                'token' => $token,
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    // public function login(Request $request){
    //     $Validated = $request->validate(
    //         [
    //         'first_name'=> 'required | string',
    //         'last_name'=> 'required | string',
    //         'SSN'=> 'required | string',
    //         'age'=> 'required | integer',
    //         'gender'=> 'required |in:male,female',
    //         'phone_number'=> 'required',
    //         'address'=> 'required | string',
    //         ]
    //         );
    //         $Patient = Patient::where('email')
    // }
>>>>>>> 66f3f95 (n commit)
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
<<<<<<< HEAD
        //
=======
        $patient = Patient::where('id', $id);
        return [
            'code' => 200,
            'message' => 'success',
            'patient' => $patient,
        ];
>>>>>>> 66f3f95 (n commit)
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
<<<<<<< HEAD
        //
=======
        $Validated = $request->validate([
            'first_name' => 'required | string',
            'last_name' => 'required | string',
            'SSN' => 'required | string',
            'age' => 'required | integer',
            'gender' => 'required |in:male,female',
            'phone_number' => 'required',
            'address' => 'required | string',
        ]);
        $patient = Patient::where('id', '=', $id);
        $patient->update($Validated);
        return [
            'code' => 200,
            'message' => 'Success',
            'patient' => $patient,
        ];
>>>>>>> 66f3f95 (n commit)
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
<<<<<<< HEAD
        //
=======
        Patient::destroy($id);
        return [
            'code' => 200,
            'message' => 'Patient deleted Successfully',
        ];
    }
    public function rate(Request $request, Doctor $doctor)
    {
        Review::create([
            'doctor_id' => $doctor->id,
            'patient_id' => Auth::id(),
            'rate' => $request->rate,
            'feedback' => $request->feedback,
        ]);
        // notify doctor he has a new rate , and admin that the patient X rate doctor Y .
        return [
            'code' => 200,
            'message' => 'success',
        ];
>>>>>>> 66f3f95 (n commit)
    }
}
