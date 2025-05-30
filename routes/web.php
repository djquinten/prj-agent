<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EmailActionController;
use App\Http\Controllers\EmailController;
use App\Http\Controllers\GoogleCalendarAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EmailController::class, 'index'])->name('emails.index');

Route::prefix('auth')->name('auth.')->group(function (): void {
    Route::get('/login', [AuthController::class, 'redirectToMicrosoft'])->name('login');
    Route::get('/callback', [AuthController::class, 'handleCallback'])->name('callback');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('google-calendar')->name('google-calendar.')->group(function (): void {
    Route::get('/login', [GoogleCalendarAuthController::class, 'redirectToGoogle'])->name('login');
    Route::get('/callback', [GoogleCalendarAuthController::class, 'handleCallback'])->name('callback');
    Route::post('/logout', [GoogleCalendarAuthController::class, 'logout'])->name('logout');
});

Route::prefix('emails')->name('emails.')->group(function (): void {
    Route::get('/search', [EmailController::class, 'search'])->name('search');
    Route::post('/sync', [EmailActionController::class, 'syncEmails'])->name('sync');
    Route::get('/ai-stats', [EmailActionController::class, 'aiStats'])->name('ai-stats');
    Route::post('/{id}/mark-read', [EmailActionController::class, 'markAsRead'])->name('mark-read');
    Route::post('/{id}/mark-unread', [EmailActionController::class, 'markAsUnread'])->name('mark-unread');
    Route::post('/{id}/toggle-read', [EmailActionController::class, 'toggleReadStatus'])->name('toggle-read');
    Route::get('/{id}', [EmailController::class, 'show'])->name('show');
});
