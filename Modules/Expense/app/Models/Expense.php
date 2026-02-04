<?php

namespace Modules\Expense\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Expense\Database\Factories\ExpenseFactory;
use Modules\ExpenseCategory\Models\ExpenseCategory;
use Modules\Account\Models\Account;
use Modules\User\Models\User;
use App\Models\Branch;

class Expense extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
         'category_id',
        'paid_by',
        'supplier_id',
        'reciept_no',
        'expense_name',
        'branch_id',
        'payment_method',
        'amount',
        'expense_date',
        'notes',
        'expense_image',
        'added_by',
        'user_id',
        'updated_by',
    ];

    // protected static function newFactory(): ExpenseFactory
    // {
    //     // return ExpenseFactory::new();
    // }


    protected $casts = [
        'amount' => 'decimal:3',
        'expense_date' => 'date',
    ];

    // Relationships
    public function category()
    {
        return $this->belongsTo(ExpenseCategory::class, 'category_id');
    }

    public function account()
    {
        return $this->belongsTo(Account::class, 'payment_method');
    }

    public function branch()
    {
        return $this->belongsTo(Branch::class, 'branch_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
