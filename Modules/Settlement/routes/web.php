<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Settlement\Http\Controllers\SettlementController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('settlement', [SettlementController::class, 'settlement'])->name('settlement');
    Route::get('get_boutiques_list', [SettlementController::class, 'get_boutiques_list'])->name('get_boutiques_list');
    Route::get('get_settlement_data', [SettlementController::class, 'get_settlement_data'])->name('get_settlement_data');
    Route::get('get_settlement_transfer_details', [SettlementController::class, 'get_settlement_transfer_details'])->name('get_settlement_transfer_details');
    Route::get('get_settlement_history', [SettlementController::class, 'get_settlement_history'])->name('get_settlement_history');
    Route::get('get_settlement_details', [SettlementController::class, 'get_settlement_details'])->name('get_settlement_details');
    Route::post('save_settlement', [SettlementController::class, 'save_settlement'])->name('save_settlement');
});
