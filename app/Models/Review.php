<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Review extends Model
{
    use HasFactory;

    protected $fillable = [
        'doc_id',
        'patient_id',
        'rate',
        'feedback',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'doc_id');
    }

    public function patient()
    {
        return $this->belongsTo(Patient::class ,'patient_id');
    }
}
