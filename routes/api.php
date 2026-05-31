<?php

use App\Http\Controllers\TelegramController;
use App\Models\Config;
use Illuminate\Support\Facades\Route;

Route::post('webhook/telegram', [TelegramController::class, 'index'])->name('webhook.telegram');
Route::get('config', function () {
    return Config::first();
});
