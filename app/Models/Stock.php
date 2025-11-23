<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Stock extends Model
{
        public function colors()
    {
        return $this->hasMany(StockColor::class);
    }

    // Stock has many sizes
    public function sizes()
    {
        return $this->hasMany(StockSize::class);
    }

    // Stock has many images
    public function images()
    {
        return $this->hasMany(StockImage::class);
    }
      public function colorSizes()
    {
        return $this->hasMany(ColorSize::class); // ← Add this line
    }
}
