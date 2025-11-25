<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SpecialOrderItem extends Model
{
    protected $fillable = [
        'special_order_id',
        'stock_id',
        'abaya_code',
        'design_name',
        'quantity',
        'price',
        'abaya_length',
        'bust',
        'sleeves_length',
        'buttons',
        'notes',
        'tailor_id',
        'tailor_status',
        'sent_to_tailor_at',
        'received_from_tailor_at',
    ];

    protected $casts = [
        'quantity' => 'integer',
        'price' => 'decimal:3',
        'abaya_length' => 'decimal:2',
        'bust' => 'decimal:2',
        'sleeves_length' => 'decimal:2',
        'buttons' => 'boolean',
    ];

    /**
     * Get the special order that owns this item
     */
    public function specialOrder(): BelongsTo
    {
        return $this->belongsTo(SpecialOrder::class);
    }

    /**
     * Get the stock/abaya reference
     */
    public function stock(): BelongsTo
    {
        return $this->belongsTo(Stock::class);
    }

    /**
     * Get the tailor assigned to this item
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }
}

