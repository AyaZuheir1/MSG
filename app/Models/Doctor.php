<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Model
{
    use HasFactory, HasApiTokens, Notifiable;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'major',
        'country',
        'average_rating',
        'phone_number',
        'image',
        'certificate',
        'gender',
        'fcm_token',
    ];
    protected $appends = ['email']; 
    protected $hidden = ['created_at', 'updated_at'];

    public function getEmailAttribute()
    {
        return $this->user ? $this->user->email : null;
    }
    protected function firstName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucwords($value),
        );
    }
    protected function lastName(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucwords($value),
        );
    }
    public function doctor()
    {
        return $this->hasOne(Doctor::class);
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

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
    public function routeNotificationForFcm()
    {
        // Return the FCM token of the user
        return $this->fcm_token;
    }
}
