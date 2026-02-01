<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AbayaMaterial extends Model
{
    protected $table = 'abaya_materials';
    
    protected $fillable = [
        'abaya_id',
        'abaya_barcode',
        'materials',
    ];

    protected $casts = [
        'materials' => 'array', // Automatically cast to/from JSON
    ];

    /**
     * Get the abaya/stock associated with this material assignment
     */
    public function abaya(): BelongsTo
    {
        return $this->belongsTo(Stock::class, 'abaya_id');
    }
}
