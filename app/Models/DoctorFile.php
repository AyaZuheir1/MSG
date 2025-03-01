<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorFile extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'file_name',
        'file_url',
        'file_type'
    ];
}
