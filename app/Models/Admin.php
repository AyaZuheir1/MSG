<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Admin extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'first_name',
        'last_name',
        'number',
        'job_title',
    ];
    protected $appends = ['email']; 
    protected $hidden = ['created_at', 'updated_at'];


    public function getEmailAttribute()
    {
        return $this->user ? $this->user->email : null;
    }
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id'); 
    }
    public function articles()
    {
        return $this->hasMany(Article::class);
    }
}
