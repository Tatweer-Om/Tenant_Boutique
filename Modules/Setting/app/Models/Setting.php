<?php

namespace Modules\Setting\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
// use Modules\Setting\Database\Factories\SettingFactory;

class Setting extends Model
{
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     */
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

    // protected static function newFactory(): SettingFactory
    // {
    //     // return SettingFactory::new();
    // }
}
