<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StockSize extends Model
{

    
    protected $table = 'stock_sizes'; // matches your migration

    protected $fillable = [
        'stock_id',
        'size_id',
        'qty',
    ];

    public $timestamps = true;
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
