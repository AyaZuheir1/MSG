<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Appointment extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id',
        'doctor_id',
        'date',
        'period',
        'start_time',
        'end_time',
        'status',
    ];
    // protected $casts = [
    //     'date' => 'date', // يحول date إلى كائن تاريخ بدلاً من string
    // ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    // public function setStartTimeAttribute($value)
    // {
    //     $this->attributes['start_time'] = Carbon::createFromFormat('h:i A', $value)->format('H:i');
    // }

    // public function setEndTimeAttribute($value)
    // {
    //     $this->attributes['end_time'] = Carbon::createFromFormat('h:i A', $value)->format('H:i');
    // }
}
