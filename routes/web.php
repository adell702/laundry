<?php

use App\Http\Controllers\ActivityLogController;
use App\Http\Controllers\CustomerController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ExpenseController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\TrackingController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\TripayPaymentController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::get('/lacak', [TrackingController::class, 'index'])->name('tracking.index');
Route::get('/lacak/hasil', [TrackingController::class, 'show'])->name('tracking.show');
Route::get('/pembayaran/online/{tripayPayment}', [TripayPaymentController::class, 'handleReturn'])
    ->middleware('signed')
    ->name('tripay.return');

Route::middleware(['auth', 'active'])->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::resource('customers', CustomerController::class);
    Route::resource('transactions', TransactionController::class)->except(['destroy']);
    Route::patch('transactions/{transaction}/status', [TransactionController::class, 'updateStatus'])
        ->name('transactions.status');
    Route::get('transactions/{transaction}/nota', [TransactionController::class, 'nota'])
        ->name('transactions.nota');
    Route::get('transactions/{transaction}/online-payment', [TripayPaymentController::class, 'create'])
        ->name('tripay-payments.create');
    Route::post('transactions/{transaction}/online-payment', [TripayPaymentController::class, 'store'])
        ->name('tripay-payments.store');
    Route::get('transactions/{transaction}/online-payment/{tripayPayment}', [TripayPaymentController::class, 'show'])
        ->name('tripay-payments.show');
    Route::post('transactions/{transaction}/online-payment/{tripayPayment}/sync', [TripayPaymentController::class, 'sync'])
        ->name('tripay-payments.sync');
    Route::get('transactions/{transaction}/online-payment/{tripayPayment}/checkout', [TripayPaymentController::class, 'checkout'])
        ->name('tripay-payments.checkout');

    Route::resource('expenses', ExpenseController::class)->except(['show']);

    Route::middleware('role:admin')->group(function () {
        Route::delete('transactions/{transaction}', [TransactionController::class, 'destroy'])
            ->name('transactions.destroy');
        Route::resource('services', ServiceController::class)->except(['show']);
        Route::resource('users', UserController::class)->except(['show']);
        Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
        Route::get('reports/export', [ReportController::class, 'export'])->name('reports.export');
        Route::get('activity-logs', [ActivityLogController::class, 'index'])->name('activity-logs.index');
    });
});

require __DIR__.'/auth.php';
