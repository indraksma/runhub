<?php

use App\Http\Controllers\Admin\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\RegistrationController;
use Illuminate\Support\Facades\Route;

Route::get('/', [EventController::class, 'index'])->name('home');
Route::get('/events/{event}', [EventController::class, 'show'])->name('events.show');
Route::get('/events/{event}/register', [RegistrationController::class, 'create'])->name('registrations.create');
Route::post('/events/{event}/register', [RegistrationController::class, 'store'])->name('registrations.store');
Route::get('/cek-pendaftaran', [RegistrationController::class, 'lookupForm'])->name('registrations.lookup');
Route::post('/cek-pendaftaran', [RegistrationController::class, 'lookup'])->middleware('throttle:6,1')->name('registrations.lookup.submit');
Route::get('/registrations/{registration}', [RegistrationController::class, 'show'])->name('registrations.show');
Route::get('/registrations/{registration}/invoice', [RegistrationController::class, 'invoice'])->name('registrations.invoice');
Route::post('/registrations/{registration}/payment-account', [RegistrationController::class, 'selectPaymentAccount'])->name('registrations.payment-account');
Route::post('/registrations/{registration}/proof', [RegistrationController::class, 'uploadProof'])->name('registrations.proof');

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'loginForm'])->name('login');
    Route::post('/login', [AuthController::class, 'login']);
});

Route::middleware('auth')->group(function () {
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');
});

Route::prefix('admin')->name('admin.')->middleware('auth')->group(function () {
    Route::middleware('payment-verifier')->group(function () {
        Route::get('/payments', [AdminController::class, 'payments'])->name('payments');
        Route::post('/payments/{payment}/approve', [AdminController::class, 'approve'])->name('payments.approve');
        Route::post('/payments/{payment}/reject', [AdminController::class, 'reject'])->name('payments.reject');
    });

    Route::middleware('admin')->group(function () {
        Route::get('/', [AdminController::class, 'dashboard'])->name('dashboard');
        Route::get('/events/create', [AdminController::class, 'createEvent'])->name('events.create');
        Route::post('/events', [AdminController::class, 'storeEvent'])->name('events.store');
        Route::get('/events/{event}/edit', [AdminController::class, 'editEvent'])->name('events.edit');
        Route::put('/events/{event}', [AdminController::class, 'updateEvent'])->name('events.update');
        Route::post('/event-description-images', [AdminController::class, 'uploadDescriptionImage'])->name('events.description-images.store');
        Route::delete('/events/{event}', [AdminController::class, 'destroyEvent'])->name('events.destroy');
        Route::post('/events/{event}/clone', [AdminController::class, 'cloneEvent'])->name('events.clone');
        Route::post('/events/{event}/categories', [AdminController::class, 'storeCategory'])->name('categories.store');
        Route::put('/categories/{category}', [AdminController::class, 'updateCategory'])->name('categories.update');
        Route::post('/categories/{category}/tiers', [AdminController::class, 'storeTier'])->name('tiers.store');
        Route::put('/tiers/{tier}', [AdminController::class, 'updateTier'])->name('tiers.update');
        Route::delete('/categories/{category}', [AdminController::class, 'destroyCategory'])->name('categories.destroy');
        Route::delete('/tiers/{tier}', [AdminController::class, 'destroyTier'])->name('tiers.destroy');
        Route::post('/events/{event}/accounts', [AdminController::class, 'storeAccount'])->name('accounts.store');
        Route::put('/accounts/{account}', [AdminController::class, 'updateAccount'])->name('accounts.update');
        Route::delete('/accounts/{account}', [AdminController::class, 'destroyAccount'])->name('accounts.destroy');
        Route::get('/registrations', [AdminController::class, 'registrations'])->name('registrations.index');
        Route::get('/registrations/export/excel', [AdminController::class, 'exportRegistrationsExcel'])->name('registrations.export.excel');
        Route::get('/registrations/export/pdf', [AdminController::class, 'exportRegistrationsPdf'])->name('registrations.export.pdf');
    });
});
