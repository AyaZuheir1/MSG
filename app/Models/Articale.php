<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Articale extends Model
{
    /** @use HasFactory<\Database\Factories\ArticaleFactory> */
    use HasFactory ,SoftDeletes;

    protected $fillable = [
        'title','content'
    ]; 
    public function admin(){
        $this->belongsTo(Admin::class);
    }
    
}
