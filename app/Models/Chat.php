<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Chat extends Model
{
    use HasFactory;
    protected $guarded = ['id'];
    protected static function boot()
    {
        parent::boot();

        static::creating(function ($model) {
            $model->id = (string) mt_rand(10000000000000, 99999999999999); // Generate random number
            // Alternatively, use UUID: $model->id = (string) Str::uuid();
        });
    }
}
