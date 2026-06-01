<?php

use App\Http\Controllers\ApiController;
use App\Http\Controllers\TelegramController;
use App\Models\Provider;
use Illuminate\Support\Facades\Route;

Route::post('webhook/telegram', [TelegramController::class, 'index'])->name('webhook.telegram');
Route::post('webhook/pakasir', [ApiController::class, 'pakasir'])->name('webhook.pakasir');
Route::get('get', function () {
    return Provider::all();
});
