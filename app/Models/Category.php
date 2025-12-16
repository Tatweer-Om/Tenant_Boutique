<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    protected $fillable = [
        'abaya',
        'category_name',
        'category_name_ar',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'abaya' => 'boolean',
    ];
}
