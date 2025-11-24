<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockHistory extends Model
{
    protected $table = 'stock_histories'; // your table name

    // Fillable fields - allow mass assignment
    protected $fillable = [
        'stock_id',
        'size_id',
        'color_id',
        'old_qty',
        'changed_qty',
        'new_qty',
        'action_type',
    ];

}
