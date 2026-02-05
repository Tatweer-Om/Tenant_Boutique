<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\ManageQuantity\Http\Controllers\ManageQuantityController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('manage_quantity', [ManageQuantityController::class, 'manage_quantity'])->name('manage_quantity');
    Route::get('movements_log', [ManageQuantityController::class, 'movements_log'])->name('movements_log');
    Route::get('get_inventory', [ManageQuantityController::class, 'get_inventory'])->name('get_inventory');
    Route::get('get_channel_inventory', [ManageQuantityController::class, 'get_channel_inventory'])->name('get_channel_inventory');
    Route::get('get_channel_stocks', [ManageQuantityController::class, 'get_channel_stocks'])->name('get_channel_stocks');
    Route::get('get_transfer_history', [ManageQuantityController::class, 'get_transfer_history'])->name('get_transfer_history');
    Route::get('get_stats', [ManageQuantityController::class, 'get_stats'])->name('get_stats');
    Route::get('export_transfers_excel', [ManageQuantityController::class, 'export_transfers_excel'])->name('export_transfers_excel');
    Route::post('execute_transfer', [ManageQuantityController::class, 'execute_transfer'])->name('execute_transfer');
});
