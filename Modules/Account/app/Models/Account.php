<?php

namespace Modules\Account\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Account\Database\Factories\AccountFactory;

class Account extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
    protected $fillable = [
        'account_name',
        'account_branch',
        'account_no',
        'opening_balance',
        'commission',
        'account_type',
        'notes',
        'account_status',
        'added_by',
        'user_id',
        'updated_by',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:3',
        'commission' => 'decimal:2',
        'account_type' => 'integer',
        'account_status' => 'integer',
    ];

    // protected static function newFactory(): AccountFactory
    // {
    //     // return AccountFactory::new();
    // }
}
