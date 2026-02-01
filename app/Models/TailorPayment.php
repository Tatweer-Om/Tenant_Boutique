<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailorPayment extends Model
{
    protected $fillable = [
        'payment_date',
        'total_amount',
        'payment_method',
        'account_id',
        'notes',
        'user_id',
        'added_by',
    ];

    protected $casts = [
        'payment_date' => 'date',
        'total_amount' => 'decimal:3',
    ];

    /**
     * Get all payment items for this payment
     */
    public function items(): HasMany
    {
        return $this->hasMany(TailorPaymentItem::class);
    }
    
    /**
     * Get the account
     */
    public function account(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'account_id');
    }
}
