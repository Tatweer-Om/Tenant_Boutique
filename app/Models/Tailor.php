<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Tailor extends Model
{
    protected $fillable = [
        'tailor_name',
        'tailor_phone',
        'tailor_address',
        'added_by',
        'updated_by',
        'user_id',
    ];

    /**
     * Get all special order items assigned to this tailor
     */
    public function specialOrderItems(): HasMany
    {
        return $this->hasMany(SpecialOrderItem::class);
    }
}
