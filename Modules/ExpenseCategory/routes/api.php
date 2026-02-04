<?php

use Illuminate\Support\Facades\Route;
use Modules\ExpenseCategory\Http\Controllers\ExpenseCategoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('expensecategories', ExpenseCategoryController::class)->names('expensecategory');
});
