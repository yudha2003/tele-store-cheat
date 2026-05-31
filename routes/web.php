<?php

use App\Http\Controllers\AdminConfigController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    abort(404);
});

Route::get('/admin/config/login', [AdminConfigController::class, 'login'])->name('admin.config.login');
Route::get('/admin/config/error', [AdminConfigController::class, 'loginError'])->name('admin.config.login_error');
Route::get('/admin/config/logout', [AdminConfigController::class, 'logout'])->name('admin.config.logout');

Route::get('/admin/config', [AdminConfigController::class, 'edit'])->name('admin.config.edit');
Route::post('/admin/config', [AdminConfigController::class, 'update'])->name('admin.config.update');
