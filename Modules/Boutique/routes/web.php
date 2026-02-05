<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Boutique\Http\Controllers\BoutiqueController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('boutique', [BoutiqueController::class, 'index'])->name('boutique');
    Route::post('add_boutique', [BoutiqueController::class, 'add_boutique'])->name('add_boutique');
    Route::get('boutique_list', [BoutiqueController::class, 'boutique_list'])->name('boutique_list');
    Route::get('boutiques/list', [BoutiqueController::class, 'getboutiques']);
    Route::get('boutiques/{id}', [BoutiqueController::class, 'show']);
    Route::get('edit_boutique/{id}', [BoutiqueController::class, 'edit_boutique'])->name('edit_boutique');
    Route::post('update_boutique', [BoutiqueController::class, 'update_boutique'])->name('update_boutique');
    Route::delete('boutique/{id}', [BoutiqueController::class, 'destroy'])->name('boutique.destroy');
    Route::get('boutique_profile/{id}', [BoutiqueController::class, 'boutique_profile'])->name('boutique_profile');
    Route::post('update_rent_invoice_status', [BoutiqueController::class, 'update_rent_invoice_status'])->name('update_rent_invoice_status');
    Route::get('get_boutique_invoices', [BoutiqueController::class, 'get_boutique_invoices'])->name('get_boutique_invoices');
    Route::post('update_invoice_payment', [BoutiqueController::class, 'update_invoice_payment'])->name('update_invoice_payment');
});
