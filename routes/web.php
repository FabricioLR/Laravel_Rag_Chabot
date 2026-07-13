<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use Illuminate\Support\Facades\Route;

if (config('admin.widget.enabled', env('ENABLE_LOCAL_WIDGET')) == true) {
    Route::get('/', function () {
        return view('local');
    })->name('local');
}

Route::middleware('guest')->prefix('admin')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])
        ->middleware('throttle:login')
        ->name('admin.login.submit');
});

Route::middleware(['auth'])->prefix('admin')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('admin.dashboard');
    Route::post('/logout', [AuthController::class, 'logout'])->name('admin.logout');

    Route::post('/domains', [DashboardController::class, 'storeDomain'])->name('admin.domains.store');
    Route::delete('/domains/{id}', [DashboardController::class, 'deleteDomain'])->name('admin.domains.delete');

    Route::get('/details/{id}', [DashboardController::class, 'details'])->name('admin.details');
});