<?php

use App\Http\Controllers\CashboxController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ImpersonationController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\UserManagementController;
use Illuminate\Support\Facades\Route;

Route::get('/', DashboardController::class)->middleware(['auth', 'verified'])->name('dashboard');
Route::redirect('/dashboard', '/');

Route::middleware('auth')->group(function () {
    Route::resource('currencies', CurrencyController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('categories', CategoryController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('cashboxes', CashboxController::class)->only(['index', 'store', 'update', 'destroy']);
    Route::resource('transactions', TransactionController::class)->except(['show']);
    Route::get('reports', [ReportController::class, 'index'])->name('reports.index');
    Route::resource('users', UserManagementController::class)->only(['index', 'store']);
    Route::post('users/{user}/impersonate', [ImpersonationController::class, 'store'])->name('users.impersonate');
    Route::post('impersonation/stop', [ImpersonationController::class, 'destroy'])->name('impersonation.destroy');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
