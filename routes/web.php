<?php

use App\Http\Controllers\DashboardController;
use App\Http\Controllers\PersonalDataController;
use App\Http\Controllers\PreferenceController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\PromotionSubmissionController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return auth()->check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/promotion-submissions', [PromotionSubmissionController::class, 'store'])
        ->name('promotion-submissions.store');
    Route::get('/promotion-submissions/{promotionSubmission}', [PromotionSubmissionController::class, 'show'])
        ->name('promotion-submissions.show');

    Route::get('/dados-pessoais', [PersonalDataController::class, 'edit'])
        ->name('personal-data.edit');
    Route::put('/dados-pessoais', [PersonalDataController::class, 'update'])
        ->name('personal-data.update');

    Route::get('/configuracoes', [PreferenceController::class, 'edit'])
        ->name('preferences.edit');
    Route::put('/configuracoes', [PreferenceController::class, 'update'])
        ->name('preferences.update');

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
