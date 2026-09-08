<?php

use App\Http\Controllers\MarketController;
use Illuminate\Support\Facades\Route;

Route::middleware('throttle:120,1')->group(function (): void {
    Route::get('/market/snapshot', [MarketController::class, 'snapshot'])->name('market.snapshot');
    Route::get('/market/indices', [MarketController::class, 'indices'])->name('market.indices');
    Route::get('/market/status', [MarketController::class, 'status'])->name('market.status');
    Route::get('/market/{symbol}/history', [MarketController::class, 'history'])->name('market.history');
});
