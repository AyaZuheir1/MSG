<?php

namespace App\Http\Controllers\API;

use App\Models\User;
use App\Models\Doctor;
use Illuminate\Http\Request;
use App\Models\DoctorRequest;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use Google\Cloud\Storage\Connection\Rest;
use App\Notifications\ReviewDoctorRequestNotification;


class AdminController extends Controller
{
    public function getDoctorRequests(Request $request)
    {
        $status = $request->query('status'); 

        if ($status) {
            if (!in_array($status, ['pending', 'approved', 'rejected'])) {
                return response()->json(['message' => 'Invalid status'], 400);
            }

            $requests = DoctorRequest::where('status', $status)->get();
        } else {
            $requests = DoctorRequest::all();
        }

        return response()->json($requests);
    }


    public function approveDoctorRequest($id)
    {
        $request = DoctorRequest::findOrFail($id);

        if ($request->status === 'pending') {
            DB::beginTransaction();

            try {

                $user = User::create([
                    'username' => strtolower($request->first_name . $request->last_name),
                    'email' => $request->email,
                    'password' => $request->password,
                    'role' => 'doctor',
                ]);
                $doctor = Doctor::create([
                    'user_id' => $user->id,
                    'first_name' => $request->first_name,
                    'last_name' => $request->last_name,
                    'major' => $request->major,
                    'phone_number' => $request->phone_number,
                    'country' => $request->country,
                    'certificate' => $request->certificate,
                    'gender' => $request->gender,
                ]);

                $doctor->save();

                $request->update(['status' => 'approved']);

                $status = 'accepted'; 
                // $doctor->notify(new ReviewDoctorRequestNotification($status));

                DB::commit();

                return response()->json(['message' => 'Doctor approved successfully!']);
            } catch (\Exception $e) {
                DB::rollBack();
                return response()->json(['message' => 'An error occurred while approving the doctor request.', 'error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['message' => 'Request already processed!'], 400);
    }


    public function rejectDoctorRequest($id)
    {
        $request = DoctorRequest::findOrFail($id);

        if ($request->status === 'pending') {
            $request->update(['status' => 'rejected']);
            return response()->json(['message' => 'Doctor request rejected successfully!']);
        }

        return response()->json(['message' => 'Request already processed!'], 400);
    }



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
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
