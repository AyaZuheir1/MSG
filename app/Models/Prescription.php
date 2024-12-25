<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Prescription extends Model
{
    use HasFactory;

    protected $fillable = [
        'p_id',
        'd_id',
        'diagnosis',
        'treatment',
        'note',
        'file',
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class, 'p_id');
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class, 'd_id');
    }

}
