<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DoctorRequest extends Model
{
    protected $table = 'doctor_requests';

    protected $fillable = [
        'first_name',
        'last_name',
        'email',
        'password',
        'country',
        'phone_number',
        'major',
        'certificate',
        'status',
        'gender',

    ];


}
