<?php


use App\Http\Controllers\Auth\{
    AuthController,
    VerifyEmailController,
    EmailVerificationNotificationController,
    PasswordResetLinkController,
    NewPasswordController,
};

use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::post('/signup', [AuthController::class, 'signup']);
    Route::post('/signin', [AuthController::class, 'login']);

    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::post('reset-password', [NewPasswordController::class, 'store'])
        ->name('password.store');

    Route::get('verify-email/{id}/{hash}', VerifyEmailController::class)
        ->middleware(['signed', 'throttle:6,1'])
        ->name('verification.verify');

    Route::middleware('auth')->group(function () {
        Route::post('email/verification-notification', [EmailVerificationNotificationController::class, 'store'])
            ->middleware(['throttle:1,5'])
            ->name('verification.send');

        Route::get('/logout', [AuthController::class, 'logout']);
    });
});
