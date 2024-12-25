<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'SSN',
        'age',
        'gender',
        'phone_number',
        'address',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class,'patient_id');
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'p_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class,'patient_id');
    }
}
