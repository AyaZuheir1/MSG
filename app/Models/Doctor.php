<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Model
{
    use HasFactory ,HasApiTokens ,Notifiable;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'major',
        'license_number',
        'country',
        'phone_number',
        'bio',
        'image',
        'fcm_token',
    ];

    public function appointments()
    {
        return $this->hasMany(Appointment::class);
    }

    public function prescriptions()
    {
        return $this->hasMany(Prescription::class, 'd_id');
    }

    public function reviews()
    {
        return $this->hasMany(Review::class, 'doc_id');
    }
}
