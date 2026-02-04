<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Area\Http\Controllers\AreaController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('areas', [AreaController::class, 'index'])->name('area');
    Route::post('areas', [AreaController::class, 'store']);
    Route::put('areas/{area}', [AreaController::class, 'update']);
    Route::delete('areas/{area}', [AreaController::class, 'destroy']);
    Route::get('areas/list', [AreaController::class, 'getAreas']);
    Route::get('areas/all', [AreaController::class, 'all']);
    Route::get('areas/{area}', [AreaController::class, 'show']);
});
