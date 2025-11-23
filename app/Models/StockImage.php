<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockImage extends Model
{
       public function stock()
    {
        return $this->belongsTo(Stock::class);
    }
}
