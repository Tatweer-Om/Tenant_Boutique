<?php

use Stancl\Tenancy\Middleware\InitializeTenancyByDomain;
use Stancl\Tenancy\Middleware\PreventAccessFromCentralDomains;
use Illuminate\Support\Facades\Route;
use Modules\Channel\Http\Controllers\ChannelController;

Route::middleware([
    'web',
    InitializeTenancyByDomain::class,
    PreventAccessFromCentralDomains::class,
])->group(function () {
    Route::get('channels', [ChannelController::class, 'index'])->name('channel');
    Route::post('channels', [ChannelController::class, 'store']);
    Route::put('channels/{channel}', [ChannelController::class, 'update']);
    Route::delete('channels/{channel}', [ChannelController::class, 'destroy']);
    Route::get('channels/list', [ChannelController::class, 'getchannels']);
    Route::get('channels/{channel}', [ChannelController::class, 'show']);
    Route::post('channels/{channel}/update-status', [ChannelController::class, 'updateStatus']);
    Route::get('channel_profile/{id}', [ChannelController::class, 'profile'])->name('channel_profile');
    Route::get('channel_profile/{id}/transfers', [ChannelController::class, 'getTransfers'])->name('channel_profile.transfers');
    Route::get('channel_profile/{id}/transfer-items', [ChannelController::class, 'getTransferItems'])->name('channel_profile.transfer_items');
    Route::get('channel_profile/{id}/sales', [ChannelController::class, 'getSales'])->name('channel_profile.sales');
    Route::get('channel_profile/{id}/item-status', [ChannelController::class, 'getItemStatus'])->name('channel_profile.item_status');
});
