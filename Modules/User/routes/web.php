<?php
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\User\Http\Controllers\UserController;
 

 
// Public routes (no auth required)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function ()   {

    Route::get('tlogin_page', [UserController::class, 'tlogin_page'])->name('tlogin_page');
    Route::post('/tlogin-user', [UserController::class, 'tlogin_user'])->name('tlogin_user');
    Route::post('/tlogout', [UserController::class, 'tlogout'])->name('tlogout');

    
});


// Protected routes (require tenant authentication)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'tenant.auth', // <- use your custom middleware
])->group(function ()   {

    Route::get('user', [UserController::class, 'index'])->name('user');
    Route::post('users', [UserController::class, 'store']);
    Route::put('users/{user}', [UserController::class, 'update']);
    Route::delete('users/{user}', [UserController::class, 'destroy']);
    Route::get('users/list', [UserController::class, 'getusers']);
    Route::get('users/{user}', [UserController::class, 'show']);
});
