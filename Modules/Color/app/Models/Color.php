<?php

namespace Modules\Color\Models;

use Illuminate\Database\Eloquent\Model;

class Color extends Model
{
    protected $fillable = [
        'color_name_en',
        'color_name_ar',
        'color_code',
        'added_by',
        'updated_by',
        'user_id',
    ];
}
