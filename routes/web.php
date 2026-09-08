<?php

use App\Http\Controllers\MarketController;
use Illuminate\Support\Facades\Route;

Route::get('/', [MarketController::class, 'index'])->name('market.index');
