<?php

use App\Filament\Pages\Auth\PendingApproval;
use App\Filament\Pages\Auth\VerifyOtp;
use App\Http\Controllers\Auth\GoogleAuthController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

// Google OAuth Authentication Routes
Route::prefix('auth/google')->group(function () {
    Route::get('/redirect', [GoogleAuthController::class, 'redirect'])->name('auth.google.redirect');
    Route::get('/callback', [GoogleAuthController::class, 'callback'])->name('auth.google.callback');
});

// Custom Filament OTP & Approval Pages
Route::middleware(['web'])->group(function () {
    Route::get('/admin/verify-otp', VerifyOtp::class)->name('filament.admin.auth.verify-otp');
    Route::get('/admin/pending-approval', PendingApproval::class)->name('filament.admin.auth.pending-approval');
});
