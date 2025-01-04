<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Articale extends Model
{
    
    use HasFactory ;

    protected $fillable = [
        'admin_id',
        'title',
        'summary',
        'image',
        'content',
        'published_at',
    ];


    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
