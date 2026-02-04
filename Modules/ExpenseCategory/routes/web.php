<?php
use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Modules\ExpenseCategory\Http\Controllers\ExpenseCategoryController;
 

// Protected routes (require tenant authentication)
Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
    // 'tenant.auth', // <- use your custom middleware
])->group(function ()   {
    
    Route::get('expense-categories', [ExpenseCategoryController::class, 'index'])->name('expense_category');
    Route::post('expense-categories', [ExpenseCategoryController::class, 'store']);
    Route::put('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'update']);
    Route::delete('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'destroy']);
    Route::get('expense-categories/list', [ExpenseCategoryController::class, 'getExpenseCategories']);
    Route::get('expense-categories/{expenseCategory}', [ExpenseCategoryController::class, 'show']);

});
