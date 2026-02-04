<?php

namespace Modules\Size\Models;

use Illuminate\Database\Eloquent\Model;

class Size extends Model
{
    protected $fillable = [
        'size_name_en',
        'size_name_ar',
        'size_code_en',
        'size_code_ar',
        'added_by',
        'updated_by',
        'user_id',
    ];
}
