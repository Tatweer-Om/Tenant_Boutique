<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorSize extends Model
{
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    public function color()
    {
        return $this->belongsTo(StockColor::class, 'color_id');
    }

    public function size()
    {
        return $this->belongsTo(StockSize::class, 'size_id');
    }
}
