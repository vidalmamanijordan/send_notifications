<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CampusController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('dashboard');
        });

        Route::resource('campus', CampusController::class);
});
