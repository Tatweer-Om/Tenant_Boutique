<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Settings extends Model
{
    protected $fillable = [
        'company_name',
        'company_email',
        'company_cr_no',
        'company_logo',
        'company_address',
        'late_delivery_weeks',
    ];

    protected $casts = [
        'late_delivery_weeks' => 'integer',
    ];

    /**
     * Get the first settings record (singleton pattern)
     */
    public static function getSettings()
    {
        return static::firstOrCreate(['id' => 1]);
    }
}
