<?php

use Illuminate\Support\Facades\Route;
use Modules\Size\Http\Controllers\SizeController;

Route::middleware(['auth:sanctum'])->prefix('v1')->group(function () {
    Route::apiResource('sizes', SizeController::class)->names('size');
});
