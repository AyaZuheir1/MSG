<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
<<<<<<< HEAD

class Doctor extends Model
{
    use HasFactory;
=======
use Laravel\Sanctum\HasApiTokens;

class Doctor extends Model
{
    use HasFactory , HasApiTokens;
>>>>>>> 66f3f95 (n commit)

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'major',
        'license_number',
        'country',
<<<<<<< HEAD
=======
        'phone_number',
>>>>>>> 66f3f95 (n commit)
        'bio',
        'image',
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
