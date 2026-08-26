<?php

use App\Http\Controllers\Admin\AdminAuthController;
use App\Http\Controllers\Admin\CertificateAdminController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\EnrollmentAdminController;
use App\Http\Controllers\CertificateController;
use App\Http\Controllers\CheckoutController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\PageController;
use App\Http\Controllers\WaiverController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public site
|--------------------------------------------------------------------------
*/
Route::get('/', [PageController::class, 'home'])->name('home');

/*
| Enrollment funnel (same-origin fetch, so CSRF applies).
*/
Route::prefix('api')->name('api.')->group(function () {
    Route::get('availability', [EnrollmentController::class, 'availability'])->name('availability');

    Route::post('enrollments', [EnrollmentController::class, 'store'])
        ->middleware('throttle:12,1')
        ->name('enrollments.store');

    Route::post('enrollments/{enrollment}/waiver', [WaiverController::class, 'store'])
        ->middleware('throttle:20,1')
        ->name('waiver.store');

    Route::get('enrollments/{enrollment}/checkout', [CheckoutController::class, 'summary'])->name('checkout.summary');
    Route::post('enrollments/{enrollment}/intent', [CheckoutController::class, 'intent'])->name('checkout.intent');
    Route::post('enrollments/{enrollment}/confirm', [CheckoutController::class, 'confirm'])->name('checkout.confirm');

    Route::get('verify', [CertificateController::class, 'verify'])
        ->middleware('throttle:30,1')
        ->name('verify');
});

/*
| Stripe → us. Outside the CSRF group; verified by signature instead.
*/
Route::post('stripe/webhook', [CheckoutController::class, 'webhook'])->name('stripe.webhook');

/*
|--------------------------------------------------------------------------
| Admin
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::get('login', [AdminAuthController::class, 'showLogin'])->middleware('guest')->name('login');
    Route::post('login', [AdminAuthController::class, 'login'])->middleware(['guest', 'throttle:5,1']);
    Route::post('logout', [AdminAuthController::class, 'logout'])->middleware('auth')->name('logout');

    Route::middleware('auth')->group(function () {
        Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

        Route::get('enrollments', [EnrollmentAdminController::class, 'index'])->name('enrollments');
        Route::get('enrollments/export', [EnrollmentAdminController::class, 'export'])->name('enrollments.export');
        Route::get('enrollments/{enrollment}', [EnrollmentAdminController::class, 'show'])->name('enrollments.show');
        Route::patch('enrollments/{enrollment}', [EnrollmentAdminController::class, 'update'])->name('enrollments.update');

        Route::get('certificates', [CertificateAdminController::class, 'index'])->name('certificates');
        Route::post('certificates', [CertificateAdminController::class, 'store'])->name('certificates.store');
        Route::delete('certificates/{certificate}', [CertificateAdminController::class, 'destroy'])->name('certificates.destroy');
    });
});
