<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\HasApiTokens;

class Patient extends Model
{
    use HasFactory, HasApiTokens;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
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
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class,'patient_id');
    }
}
