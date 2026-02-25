<?php

use App\Http\Controllers\Api\V1\Auth\AuthController;
use App\Http\Controllers\Api\V1\DashboardController;
use App\Http\Controllers\Api\V1\InvoiceController;
use App\Http\Controllers\Api\V1\PaymentController;
use App\Http\Controllers\Api\V1\PaymentMethodController;
use App\Http\Controllers\Api\V1\PlanController;
use App\Http\Controllers\Api\V1\SubscriptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->name('v1.')->group(function () {
    Route::prefix('auth')->name('auth.')->group(function () {
        Route::post('register', [AuthController::class, 'register'])->name('register');
        Route::post('login', [AuthController::class, 'login'])->name('login');

        Route::middleware('auth:sanctum')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('logout');
            Route::get('me', [AuthController::class, 'me'])->name('me');
        });
    });

    Route::middleware('auth:sanctum')->group(function () {
        Route::get('dashboard', [DashboardController::class, 'show'])->name('dashboard');

        Route::prefix('invoices')->name('invoices.')->group(function () {
            Route::get('/', [InvoiceController::class, 'index'])->name('index');
            Route::get('{id}', [InvoiceController::class, 'show'])->name('show');
            Route::post('{id}/payments', [PaymentController::class, 'store'])->name('payments.store');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [PaymentController::class, 'index'])->name('index');
            Route::get('{id}', [PaymentController::class, 'show'])->name('show');
        });

        Route::get('plans', [PlanController::class, 'index'])->name('plans.index');

        Route::prefix('subscription')->name('subscription.')->group(function () {
            Route::get('/', [SubscriptionController::class, 'show'])->name('show');
            Route::post('/', [SubscriptionController::class, 'store'])->name('store');
            Route::patch('plan', [SubscriptionController::class, 'changePlan'])->name('change-plan');
            Route::delete('/', [SubscriptionController::class, 'cancel'])->name('cancel');
        });

        Route::prefix('payment-methods')->name('payment-methods.')->group(function () {
            Route::get('/', [PaymentMethodController::class, 'index'])->name('index');
            Route::post('/', [PaymentMethodController::class, 'store'])->name('store');
            Route::get('{id}', [PaymentMethodController::class, 'show'])->name('show');
            Route::delete('{id}', [PaymentMethodController::class, 'destroy'])->name('destroy');
            Route::patch('{id}/default', [PaymentMethodController::class, 'setDefault'])->name('set-default');
        });
    });
});
