<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\SoftDeletes;

class Article extends Model
{
    use HasFactory,SoftDeletes;

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
        static::deleted(function(Article $article){
            $article->status = 'deleted';
            $article->save();
        });
        static::restoring(function(Article $article){
            $article->status = 'published';
            $article->save();
        });
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }
}
