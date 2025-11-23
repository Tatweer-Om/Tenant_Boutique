<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockColor extends Model
{
        public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    // Optional: If you have a Color model
      public function color()
    {
        return $this->belongsTo(Color::class, 'color_id', 'id');
    }
}
