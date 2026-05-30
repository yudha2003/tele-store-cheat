<?php

use App\Http\Controllers\TelegramController;
use App\Models\History;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::post('webhook/telegram', [TelegramController::class, 'index'])->name('webhook.telegram');
Route::get('user', function () {
    return User::all();
});
Route::get('history', function () {
    return History::all();
});
