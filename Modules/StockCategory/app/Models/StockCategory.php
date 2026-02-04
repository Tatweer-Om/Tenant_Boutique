<?php

namespace Modules\StockCategory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\StockCategory\Database\Factories\StockCategoryFactory;

class StockCategory extends Model
{
    use HasFactory;
    protected $table = 'categories';
    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = ['abaya',
        'category_name',
        'category_name_ar',
        'notes',
        'added_by',
        'user_id',
        'updated_by',];

    protected $casts = [
        'abaya' => 'boolean',
    ];
    // protected static function newFactory(): StockCategoryFactory
    // {
    //     // return StockCategoryFactory::new();
    // }
}
