<?php
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\Account\Http\Controllers\AccountController;
 

// Protected routes (require tenant authentication)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'tenant.auth', // <- use your custom middleware
])->group(function ()   {
    
    Route::get('accounts', [AccountController::class, 'index'])->name('account');
    Route::post('accounts', [AccountController::class, 'store']);
    Route::put('accounts/{account}', [AccountController::class, 'update']);
    Route::delete('accounts/{account}', [AccountController::class, 'destroy']);
    Route::get('accounts/list', [AccountController::class, 'getAccounts']);
    Route::get('accounts/all', [AccountController::class, 'all']);
    Route::get('accounts/{account}', [AccountController::class, 'show']);

});
