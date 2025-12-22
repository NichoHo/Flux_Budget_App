<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\TransactionController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\AnalyticsController;

// 1. Localization Route (for language only)
Route::get('/lang/{locale}', function ($locale) {
    if (in_array($locale, ['en', 'id'])) {
        session(['locale' => $locale]);
    }
    return redirect()->back();
})->name('lang.switch');

// 1a. Currency Route (for currency only) - Use controller
Route::get('/currency/{currency}', [CurrencyController::class, 'switch'])
    ->name('currency.switch');

// 2. Guest Routes
Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
    Route::get('/register', [AuthController::class, 'showRegister'])->name('register');
    Route::post('/register', [AuthController::class, 'register']);
});

// 3. Protected Routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
    
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
    
    Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics');
    Route::get('/analytics/export', [AnalyticsController::class, 'export'])->name('analytics.export');
    
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings');

    Route::get('/transactions/calendar', [TransactionController::class, 'calendar'])->name('transactions.calendar');
    Route::resource('transactions', TransactionController::class);

    Route::get('/budget', [App\Http\Controllers\BudgetController::class, 'index'])->name('budget.index');
    Route::get('/budget/create', [App\Http\Controllers\BudgetController::class, 'create'])->name('budget.create');
    Route::post('/budget', [App\Http\Controllers\BudgetController::class, 'store'])->name('budget.store');
    Route::get('/budget/{budget}/edit', [App\Http\Controllers\BudgetController::class, 'edit'])->name('budget.edit');
    Route::put('/budget/{budget}', [App\Http\Controllers\BudgetController::class, 'update'])->name('budget.update');
    Route::delete('/budget/{budget}', [App\Http\Controllers\BudgetController::class, 'destroy'])->name('budget.destroy');

    Route::resource('recurring', App\Http\Controllers\RecurringBillController::class);
});

// 4. Default Redirect
Route::get('/', function () {
    if (Auth::check()) {
        return redirect()->route('dashboard');
    }
    return view('landing');
})->name('home');