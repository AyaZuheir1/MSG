<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\DoctorFile;
use App\Services\SupabaseStorageService;

class DoctorFileController extends Controller
{
    protected $supabaseStorage;

    public function __construct(SupabaseStorageService $supabaseStorage)
    {
        $this->supabaseStorage = $supabaseStorage;
    }

    public function upload(Request $request)
    {
        // return env('SUPABASE_URL') . env('SUPABASE_BUCKET'). " -----  ".  env('SUPABASE_ANON_KEY');
        $request->validate([
            'doctor_id' => 'required|exists:doctors,id',
            'file' => 'required|file|max:2048',
        ]);

        $file = $request->file('file');
        $path = 'doctor_files/' . $request->doctor_id; // Folder inside Supabase bucket

        $result = $this->supabaseStorage->uploadFile($file, $path);
        // return "asuccess" . $result;
        if ($result) {
            $doctorFile = DoctorFile::create([
                'doctor_id' => $request->doctor_id,
                'file_name' => $result['file_name'],
                'file_url'  => $result['file_url'],
                'file_type' => $file->getClientMimeType(),
            ]);

            return response()->json(['message' => 'File uploaded successfully', 'data' => $doctorFile], 200);
        }

        return response()->json(['message' => 'File upload failed'], 500);
    }

    public function listFiles($doctor_id)
    {
        $files = DoctorFile::where('doctor_id', $doctor_id)->get();
        return response()->json($files);
    }
}
