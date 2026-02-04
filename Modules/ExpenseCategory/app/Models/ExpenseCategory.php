<?php

namespace Modules\ExpenseCategory\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\ExpenseCategory\Database\Factories\ExpenseCategoryFactory;

class ExpenseCategory extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'category_name',
        'notes',
        'added_by',
        'user_id',
        'updated_by',
    ];

    // protected static function newFactory(): ExpenseCategoryFactory
    // {
    //     // return ExpenseCategoryFactory::new();
    // }
}
