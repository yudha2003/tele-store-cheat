<?php

use App\Http\Controllers\TelegramController;
use Illuminate\Support\Facades\Route;

Route::post('webhook/telegram', [TelegramController::class, 'index'])->name('webhook.telegram');
