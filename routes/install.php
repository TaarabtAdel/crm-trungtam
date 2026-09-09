<?php

use App\Http\Controllers\InstallController;
use App\Http\Middleware\PreventInstallWhenInstalled;
use Illuminate\Support\Facades\Route;

Route::middleware([PreventInstallWhenInstalled::class, 'web'])->prefix('install')->name('install.')->group(function () {
    Route::get('/', [InstallController::class, 'index'])->name('index');
    Route::get('/database', [InstallController::class, 'showDatabase'])->name('database');
    Route::post('/database', [InstallController::class, 'storeDatabase'])->name('database.store');
    Route::get('/admin', [InstallController::class, 'showAdmin'])->name('admin');
    Route::post('/admin', [InstallController::class, 'storeAdmin'])->name('admin.store');
});

Route::middleware('web')->prefix('install')->name('install.')->group(function () {
    Route::get('/done', [InstallController::class, 'done'])->name('done');
});
