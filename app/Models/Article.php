<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Article extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_id',
        'title',
        'summary',
        'image',
        'content',
        'published_at',
    ];

    protected function title(): Attribute
    {
        return Attribute::make(
            get: fn (string $value) => ucwords($value),
        );
    }

    protected  static function booted(){
    
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
