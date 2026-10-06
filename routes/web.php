<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Merchant\CredentialController;
use App\Http\Controllers\Admin\FormController;

Route::get('/', function () {
    return view('welcome');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('merchant.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', function () {
        return view('admin.dashboard');
    })->name('dashboard');

    Route::resource('merchants', MerchantController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::resource('forms', FormController::class);
    Route::patch('forms/{form}/toggle-status', [FormController::class, 'toggleStatus'])->name('forms.toggle-status');
});

Route::middleware(['auth', 'verified', 'role:merchant|admin'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/dashboard', function () {
        return view('merchant.dashboard');
    })->name('dashboard');

    Route::get('/credentials', [CredentialController::class, 'edit'])->name('credentials.edit');
    Route::post('/credentials', [CredentialController::class, 'update'])->name('credentials.update');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';