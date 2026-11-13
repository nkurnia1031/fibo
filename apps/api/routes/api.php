<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StudentAccountController;
use App\Http\Controllers\Api\V1\StudentAttemptController;
use App\Http\Controllers\Api\V1\StudentAuthController;
use App\Http\Controllers\Api\V1\StudentWalletController;
use App\Http\Controllers\Api\V1\TestPackageController;
use App\Http\Controllers\Api\V1\WalletAdminController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureStaffIsActive;
use App\Http\Middleware\EnsureStudentEmailIsVerified;
use App\Http\Middleware\EnsureStudentIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('v1')->group(function (): void {
    Route::get('/auth/csrf', [AuthController::class, 'csrf']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

    Route::post('/student/register', [StudentAuthController::class, 'register'])->middleware('throttle:student-register');
    Route::post('/student/login', [StudentAuthController::class, 'login'])->middleware('throttle:student-login');
    Route::get('/student/email/verify/{student}/{hash}', [StudentAuthController::class, 'verify'])
        ->middleware(['signed', 'throttle:6,1'])->name('student.verification.verify');
    Route::post('/student/password/forgot', [StudentAuthController::class, 'forgotPassword'])->middleware('throttle:student-password-reset');
    Route::post('/student/password/reset', [StudentAuthController::class, 'resetPassword'])->middleware('throttle:student-password-reset');
    Route::get('/student/catalog', [StudentWalletController::class, 'catalog']);
    Route::get('/student/top-up/options', [StudentWalletController::class, 'topUpOptions']);

    Route::middleware(['auth:student', EnsureStudentIsActive::class])->prefix('student')->group(function (): void {
        Route::get('/me', [StudentAuthController::class, 'me']);
        Route::post('/logout', [StudentAuthController::class, 'logout']);
        Route::post('/email/verification-notification', [StudentAuthController::class, 'resendVerification'])->middleware('throttle:6,1');
        Route::get('/wallet', [StudentWalletController::class, 'wallet']);
        Route::get('/top-ups', [StudentWalletController::class, 'topUps']);
        Route::get('/accesses', [StudentWalletController::class, 'accesses']);
        Route::get('/attempts', [StudentAttemptController::class, 'index']);
        Route::get('/attempts/{attemptId}', [StudentAttemptController::class, 'show']);
        Route::post('/attempts/{attemptId}/submit', [StudentAttemptController::class, 'submit']);
        Route::put('/attempts/{attemptId}/answers/{questionId}', [StudentAttemptController::class, 'answer']);
        Route::middleware(EnsureStudentEmailIsVerified::class)->group(function (): void {
            Route::post('/top-ups', [StudentWalletController::class, 'requestTopUp'])->middleware('throttle:10,1');
            Route::post('/offers/{code}/purchase', [StudentWalletController::class, 'purchase'])->middleware('throttle:10,1');
            Route::post('/accesses/{accessId}/attempts', [StudentAttemptController::class, 'start']);
        });
    });

    Route::middleware(['auth', EnsureStaffIsActive::class])->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::put('/auth/password', [AuthController::class, 'changePassword']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

        Route::middleware(EnsurePasswordChanged::class)->group(function (): void {
            Route::get('/questions', [QuestionController::class, 'index']);
            Route::post('/questions', [QuestionController::class, 'store']);
            Route::get('/questions/{code}/revisions/{revision}', [QuestionController::class, 'show']);
            Route::put('/questions/{code}/revisions/{revision}', [QuestionController::class, 'update']);
            Route::delete('/questions/{code}/revisions/{revision}', [QuestionController::class, 'destroy']);
            Route::post('/questions/{code}/revisions', [QuestionController::class, 'revise']);
            Route::post('/questions/{code}/revisions/{revision}/transition', [QuestionController::class, 'transition']);

            Route::get('/packages', [TestPackageController::class, 'index']);
            Route::post('/packages', [TestPackageController::class, 'store']);
            Route::get('/packages/{code}/versions/{version}', [TestPackageController::class, 'show']);
            Route::put('/packages/{code}/versions/{version}', [TestPackageController::class, 'update']);
            Route::delete('/packages/{code}/versions/{version}', [TestPackageController::class, 'destroy']);
            Route::post('/packages/{code}/revisions', [TestPackageController::class, 'revise']);
            Route::post('/packages/{code}/versions/{version}/transition', [TestPackageController::class, 'transition']);

            Route::get('/staff', [StaffController::class, 'index']);
            Route::post('/staff', [StaffController::class, 'store']);
            Route::put('/staff/{staffCode}', [StaffController::class, 'update']);
            Route::delete('/staff/{staffCode}', [StaffController::class, 'destroy']);
            Route::post('/staff/{staffCode}/password-reset', [StaffController::class, 'resetPassword']);

            Route::get('/students', [StudentAccountController::class, 'index']);
            Route::post('/students', [StudentAccountController::class, 'store']);
            Route::put('/students/{studentAccount}', [StudentAccountController::class, 'update']);
            Route::delete('/students/{studentAccount}', [StudentAccountController::class, 'destroy']);
            Route::patch('/students/{studentAccount}/close', [StudentAccountController::class, 'close']);
            Route::post('/students/{studentAccount}/credential-reset', [StudentAccountController::class, 'resetCredential']);

            Route::get('/payment-methods', [PaymentMethodController::class, 'index']);
            Route::post('/payment-methods', [PaymentMethodController::class, 'store']);
            Route::patch('/payment-methods/{code}', [PaymentMethodController::class, 'update']);
            Route::delete('/payment-methods/{code}', [PaymentMethodController::class, 'destroy']);

            Route::get('/wallet-summary', [WalletAdminController::class, 'summary']);
            Route::get('/wallet-settings', [WalletAdminController::class, 'settings']);
            Route::put('/wallet-settings', [WalletAdminController::class, 'updateSettings']);
            Route::get('/point-bundles', [WalletAdminController::class, 'bundles']);
            Route::post('/point-bundles', [WalletAdminController::class, 'storeBundle']);
            Route::put('/point-bundles/{code}', [WalletAdminController::class, 'updateBundle']);
            Route::delete('/point-bundles/{code}', [WalletAdminController::class, 'deleteBundle']);
            Route::get('/access-offers', [WalletAdminController::class, 'offers']);
            Route::get('/package-options', [WalletAdminController::class, 'packageOptions']);
            Route::post('/access-offers', [WalletAdminController::class, 'storeOffer']);
            Route::put('/access-offers/{code}', [WalletAdminController::class, 'updateOffer']);
            Route::delete('/access-offers/{code}', [WalletAdminController::class, 'deleteOffer']);
            Route::get('/top-ups', [WalletAdminController::class, 'topUps']);
            Route::post('/top-ups/{topupId}/review', [WalletAdminController::class, 'reviewTopUp']);
            Route::get('/top-ups/{topupId}/proof', [WalletAdminController::class, 'proof']);
            Route::post('/students/{studentAccount}/points', [WalletAdminController::class, 'adjustPoints']);
        });
    });
});
