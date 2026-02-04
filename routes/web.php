<?php

use Illuminate\Support\Facades\Route; 
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TenantController;
use App\Http\Controllers\DashboardController;


foreach (config('tenancy.central_domains') as $domain) {
    
    Route::domain($domain)->group(function () {
        Route::get('/', function () {
            return view('welcome');
        }); 
        Route::get('showLogin', [AuthController::class, 'showLogin'])->name('showLogin'); 
        Route::post('login', [AuthController::class, 'login'])->name('login'); 
      

        Route::middleware(['auth'])->group(function () {
            Route::get('/central_dashboard', [DashboardController::class, 'index'])->name('central_dashboard');
            Route::get('logout', [AuthController::class, 'logout'])->name('logout'); 
            Route::resource('tenants', TenantController::class);
        });
    });
}