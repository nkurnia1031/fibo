<?php

use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\QuestionController;
use App\Http\Controllers\Api\V1\StaffController;
use App\Http\Controllers\Api\V1\StudentAccountController;
use App\Http\Controllers\Api\V1\TestPackageController;
use App\Http\Middleware\EnsurePasswordChanged;
use App\Http\Middleware\EnsureStaffIsActive;
use Illuminate\Support\Facades\Route;

Route::middleware('web')->prefix('v1')->group(function (): void {
    Route::get('/auth/csrf', [AuthController::class, 'csrf']);
    Route::post('/auth/login', [AuthController::class, 'login'])->middleware('throttle:login');

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
        });
    });
});
