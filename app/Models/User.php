<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    public function admin()
    {
        return $this->hasOne(Admin::class,'user_id');
    }

    public function doctor()
    {
        return $this->hasOne(Doctor::class,'user_id');
    }

    public function patient()
    {
        return $this->hasOne(Patient::class ,'user_id');
    }

    public function notifications()
    {
        return $this->hasMany(Notification::class, 'sender_id');
    }
}