<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Stock\Http\Controllers\StockController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('view_stock', [StockController::class, 'view_stock'])->name('view_stock');
    Route::get('add_stock', [StockController::class, 'add_stock_form'])->name('add_stock');
    Route::post('add_stock', [StockController::class, 'add_stock'])->name('add_stock.post');
    Route::get('stock/list', [StockController::class, 'getstock']);
    Route::get('edit_stock/{id}', [StockController::class, 'edit_stock'])->name('edit_stock');
    Route::post('update_stock', [StockController::class, 'update_stock'])->name('update_stock');
    Route::delete('delete_stock/{id}', [StockController::class, 'delete_stock'])->name('delete_stock');
    Route::delete('stock/image/{id}', [StockController::class, 'deleteImage'])->name('stock.image.delete');
    Route::get('stock_detail', [StockController::class, 'stock_detail'])->name('stock_detail');
    Route::get('get_stock_quantity', [StockController::class, 'get_stock_quantity'])->name('get_stock_quantity');
    Route::get('get_full_stock_details', [StockController::class, 'get_full_stock_details'])->name('get_full_stock_details');
    Route::post('add_quantity', [StockController::class, 'add_quantity'])->name('add_quantity');
    Route::get('stock/comprehensive-audit', [StockController::class, 'comprehensiveAudit'])->name('stock.comprehensive_audit');
    Route::get('stock/comprehensive-audit/list', [StockController::class, 'getComprehensiveAudit'])->name('stock.comprehensive_audit.list');
});
