<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Customer\Http\Controllers\CustomerController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('customers', [CustomerController::class, 'index'])->name('customer');
    Route::post('customers', [CustomerController::class, 'store']);
    Route::put('customers/{customer}', [CustomerController::class, 'update']);
    Route::delete('customers/{customer}', [CustomerController::class, 'destroy']);
    Route::get('customers/list', [CustomerController::class, 'getCustomers']);
    Route::get('customer_profile/{id}', [CustomerController::class, 'profile'])->name('customer_profile');
    Route::get('customers/{customer}', [CustomerController::class, 'show']);
});
