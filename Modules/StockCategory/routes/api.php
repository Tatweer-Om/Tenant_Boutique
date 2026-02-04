<?php

use Illuminate\Support\Facades\Route;
use Modules\StockCategory\Http\Controllers\StockCategoryController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('stockcategories', StockCategoryController::class)->names('stockcategory');
});
