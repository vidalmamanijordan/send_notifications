<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('Welcome');
})->name('home');

Route::get('register', fn () => redirect('/'))->name('register');
Route::post('register', fn () => abort(404));

Route::get('dashboard', [\App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::get('help', [\App\Http\Controllers\HelpController::class, 'index'])
    ->middleware(['auth', 'verified'])
    ->name('help');

require __DIR__.'/settings.php';
require __DIR__.'/admin.php';
