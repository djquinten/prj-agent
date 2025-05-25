<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'redirectToMicrosoft'])->name('login');
    Route::get('/callback', [AuthController::class, 'handleCallback'])->name('callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

