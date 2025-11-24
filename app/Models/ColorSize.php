<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ColorSize extends Model
{

      protected $table = 'color_sizes'; // match your table name

    protected $fillable = [
        'stock_id',
        'color_id',
        'size_id',
        'qty',
    ];

    public $timestamps = true;
    
    public function stock()
    {
        return $this->belongsTo(Stock::class);
    }

      // Relationship to Size
    public function size()
    {
        return $this->belongsTo(Size::class, 'size_id', 'id');
    }

    // Relationship to Color
    public function color()
    {
        return $this->belongsTo(Color::class, 'color_id', 'id');
    }

}
