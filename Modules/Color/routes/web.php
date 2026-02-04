<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Color\Http\Controllers\ColorController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('color', [ColorController::class, 'index'])->name('color');
    Route::post('colors', [ColorController::class, 'store']);
    Route::put('colors/{color}', [ColorController::class, 'update']);
    Route::delete('colors/{color}', [ColorController::class, 'destroy']);
    Route::get('colors/list', [ColorController::class, 'getcolors']);
    Route::get('colors/{color}', [ColorController::class, 'show']);
});
