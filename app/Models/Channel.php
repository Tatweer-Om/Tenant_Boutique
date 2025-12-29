<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Channel extends Model
{
    protected $fillable = [
        'channel_name_en',
        'channel_name_ar',
        'added_by',
        'updated_by',
        'user_id',
        'status_for_pos'
    ];
}
