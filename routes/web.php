<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\MerchantController;
use App\Http\Controllers\Admin\FormController;
use App\Http\Controllers\FormSubmissionController;
use App\Http\Controllers\RazorpayWebhookController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Merchant\DashboardController as MerchantDashboardController;
use App\Http\Controllers\SubmissionController;

Route::get('/', function () {
    if (auth()->check()) {
        return redirect()->route('dashboard');
    }

    return redirect()->route('login');
});

Route::get('/dashboard', function () {
    if (auth()->user()->hasRole('admin')) {
        return redirect()->route('admin.dashboard');
    }

    return redirect()->route('merchant.dashboard');
})->middleware(['auth', 'verified'])->name('dashboard');

Route::middleware(['auth', 'verified', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    Route::resource('merchants', MerchantController::class)->except(['show', 'edit', 'update', 'destroy']);
    Route::resource('forms', FormController::class);
    Route::patch('forms/{form}/toggle-status', [FormController::class, 'toggleStatus'])->name('forms.toggle-status');

    Route::get('forms/{form}/payment', [FormController::class, 'paymentEdit'])->name('forms.payment');
    Route::post('forms/{form}/payment', [FormController::class, 'paymentStore'])->name('forms.payment.store');
    Route::get('forms/{form}/receipt', [FormController::class, 'receiptEdit'])->name('forms.receipt');
    Route::post('forms/{form}/receipt', [FormController::class, 'receiptStore'])->name('forms.receipt.store');
});

Route::middleware(['auth', 'verified', 'role:merchant|admin'])->prefix('merchant')->name('merchant.')->group(function () {
    Route::get('/dashboard', [MerchantDashboardController::class, 'index'])->name('dashboard');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::middleware(['auth', 'verified', 'role:merchant|admin'])->group(function () {
    Route::get('/forms/{form}/submissions', [SubmissionController::class, 'index'])->name('submissions.index');
    Route::get('/forms/{form}/submissions/export', [SubmissionController::class, 'export'])->name('submissions.export');
});

require __DIR__ . '/auth.php';

Route::get('/f/{uuid}', [FormSubmissionController::class, 'show'])->name('form.public');
Route::post('/f/{uuid}', [FormSubmissionController::class, 'store'])
    ->middleware('throttle:10,1')
    ->name('form.submit');
Route::post('/f/{uuid}/payment-callback', [FormSubmissionController::class, 'paymentCallback'])
    ->name('form.payment.callback');

Route::post('/webhooks/razorpay/{uuid}', [RazorpayWebhookController::class, 'handle'])
    ->name('webhooks.razorpay');
