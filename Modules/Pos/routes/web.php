<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Pos\Http\Controllers\PosController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('pos', [PosController::class, 'index'])->name('pos');
    Route::get('pos/stock/{id}', [PosController::class, 'getStockDetails'])->name('pos.stock.details');
    Route::get('pos/customers', [PosController::class, 'searchCustomers'])->name('pos.customers.search');
    Route::post('pos/orders', [PosController::class, 'store'])->name('pos.orders.store');
    Route::post('pos/shipping-fee', [PosController::class, 'getShippingFee'])->name('pos.shipping_fee');
    Route::get('pos/cities', [PosController::class, 'citiesByArea'])->name('pos.cities.by_area');
    Route::get('pos/orders/list', [PosController::class, 'ordersList'])->name('pos.orders.list');
    Route::get('pos/orders/list/data', [PosController::class, 'getOrdersList'])->name('pos.orders.list.data');
    Route::post('pos/orders/update-delivery-status', [PosController::class, 'updateDeliveryStatus'])->name('pos.orders.update_delivery_status');
    Route::get('pos/active-channels', [PosController::class, 'getActiveChannels'])->name('pos.active_channels');
    Route::post('pos/select-channel', [PosController::class, 'selectChannel'])->name('pos.select_channel');
    Route::get('pos_bill', [PosController::class, 'pos_bill'])->name('pos_bill');
});
