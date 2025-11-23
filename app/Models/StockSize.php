<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockSize extends Model
{
     public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

    // Optional: If you have a Size model
   public function size()
    {
        return $this->belongsTo(Size::class, 'size_id', 'id');
    }
}
