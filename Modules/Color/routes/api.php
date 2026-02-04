<?php

use Illuminate\Support\Facades\Route;
use Modules\Color\Http\Controllers\ColorController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('colors', ColorController::class)->names('color');
});
