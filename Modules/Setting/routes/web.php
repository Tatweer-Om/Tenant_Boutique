<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Setting\Http\Controllers\SettingController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('settings', [SettingController::class, 'index'])->name('settings');
    Route::get('settings/get', [SettingController::class, 'getSettings'])->name('settings.get');
    Route::post('settings/update', [SettingController::class, 'update'])->name('settings.update');

});

// Settings Routes
