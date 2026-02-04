<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Size\Http\Controllers\SizeController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('size', [SizeController::class, 'index'])->name('size');
    Route::post('sizes', [SizeController::class, 'store']);
    Route::put('sizes/{size}', [SizeController::class, 'update']);
    Route::delete('sizes/{size}', [SizeController::class, 'destroy']);
    Route::get('sizes/list', [SizeController::class, 'getSizes']);
    Route::get('sizes/{size}', [SizeController::class, 'show']);
});
