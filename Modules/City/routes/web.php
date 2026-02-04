<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\City\Http\Controllers\CityController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('cities', [CityController::class, 'index'])->name('city');
    Route::post('cities', [CityController::class, 'store']);
    Route::put('cities/{city}', [CityController::class, 'update']);
    Route::delete('cities/{city}', [CityController::class, 'destroy']);
    Route::get('cities/list', [CityController::class, 'getCities']);
    Route::get('cities/by-area', [CityController::class, 'byArea']);
    Route::get('cities/{city}', [CityController::class, 'show']);
});
