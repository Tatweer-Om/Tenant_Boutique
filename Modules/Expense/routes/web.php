<?php
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\Expense\Http\Controllers\ExpenseController;
 

// Protected routes (require tenant authentication)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'tenant.auth', // <- use your custom middleware
])->group(function ()   {
    
    Route::get('expenses', [ExpenseController::class, 'index'])->name('expense');
Route::post('expenses', [ExpenseController::class, 'store']);
Route::put('expenses/{expense}', [ExpenseController::class, 'update']);
Route::delete('expenses/{expense}', [ExpenseController::class, 'destroy']);
Route::get('expenses/list', [ExpenseController::class, 'getExpenses']);
Route::get('expenses/{expense}', [ExpenseController::class, 'show']);

});
