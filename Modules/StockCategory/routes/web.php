<?php
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\StockCategory\Http\Controllers\StockCategoryController;
 

// Protected routes (require tenant authentication)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'tenant.auth', // <- use your custom middleware
])->group(function ()   {

    Route::get('categories', [StockCategoryController::class, 'index'])->name('category');
    Route::post('categories', [StockCategoryController::class, 'store']);
    Route::put('categories/{category}', [StockCategoryController::class, 'update']);
    Route::delete('categories/{category}', [StockCategoryController::class, 'destroy']);
    Route::get('categories/list', [StockCategoryController::class, 'getCategories']);
    Route::get('categories/{category}', [StockCategoryController::class, 'show']);

});
