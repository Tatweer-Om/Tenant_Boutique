<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TailorMaterial extends Model
{
    protected $table = 'tailor_materials';
    
    protected $fillable = [
        'tailor_id',
        'material_id',
        'abaya_id',
        'quantity',
        'abayas_expected',
        'status',
        'sent_date',
        'completed_date',
        'added_by',
        'user_id',
    ];

    protected $casts = [
        'quantity' => 'decimal:2',
        'abayas_expected' => 'integer',
        'sent_date' => 'date',
        'completed_date' => 'date',
    ];

    /**
     * Get the tailor that owns this material assignment
     */
    public function tailor(): BelongsTo
    {
        return $this->belongsTo(Tailor::class);
    }

    /**
     * Get the material that was sent
     */
    public function material(): BelongsTo
    {
        return $this->belongsTo(Material::class);
    }

    /**
     * Get the abaya/stock associated with this material
     */
    public function abaya(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'abaya_id');
    }
}

