<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ExcelUploadController;
use App\Http\Controllers\Admin\ExpiredEvaluationController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\NotificationBatchController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\ProgramController;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('dashboard');
        });

        Route::resource('campus', CampusController::class);
        Route::resource(
            'academic-periods',
            AcademicPeriodController::class
        )->except(['create', 'edit', 'show']);
        Route::resource(
            'faculties',
            FacultyController::class
        )->except(['create', 'edit', 'show']);
        Route::resource(
            'programs',
            ProgramController::class
        )->except(['create', 'edit', 'show']);

        Route::resource(
            'courses',
            CourseController::class
        )->except(['create', 'edit', 'show']);
        Route::resource(
            'excel-uploads',
            ExcelUploadController::class
        )->only(['index', 'store', 'destroy']);
        Route::get(
            'expired-evaluations',
            [ExpiredEvaluationController::class, 'index']
        )->name('expired-evaluations.index');

        Route::resource(
            'notification-batches',
            NotificationBatchController::class
        )->only(['index', 'show']);

        Route::post(
            'notification-batches/build',
            [NotificationBatchController::class, 'build']
        )->name('notification-batches.build');

        Route::resource(
            'notification-templates',
            NotificationTemplateController::class
        )->except(['create', 'edit']);
    });
