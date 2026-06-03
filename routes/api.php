<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\TelegramController;
use App\Models\Config;
use Illuminate\Support\Facades\Route;

Route::post('webhook/telegram', [TelegramController::class, 'index'])->name('webhook.telegram');
Route::post('callback/pakasir', [ApiController::class, 'pakasir'])->name('callback.pakasir');
Route::get('get', function () {
    return Config::all();
});

Route::get('attic', function () {
});
