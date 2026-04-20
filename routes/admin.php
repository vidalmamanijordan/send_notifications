<?php

use App\Http\Controllers\Admin\AcademicPeriodController;
use App\Http\Controllers\Admin\AcademicPeriodSwitchController;
use App\Http\Controllers\Admin\CampusController;
use App\Http\Controllers\Admin\CourseController;
use App\Http\Controllers\Admin\ExcelUploadController;
use App\Http\Controllers\Admin\ExpiredEvaluationController;
use App\Http\Controllers\Admin\FacultyController;
use App\Http\Controllers\Admin\ItContactController;
use App\Http\Controllers\Admin\NotificationBatchController;
use App\Http\Controllers\Admin\NotificationTemplateController;
use App\Http\Controllers\Admin\OfficeController;
use App\Http\Controllers\Admin\PersonImportController;
use App\Http\Controllers\Admin\ProgramController;
use App\Http\Controllers\Admin\ReportController;
use App\Http\Controllers\Admin\RoleController;
use App\Http\Controllers\Admin\TeacherController;
use App\Http\Controllers\Admin\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware(['auth', 'verified'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/', function () {
            return redirect()->route('dashboard');
        });

        // ┌─────────────────────────────────────────────────────────────────────┐
        // │  SUPERADMIN — gestión de roles del sistema                          │
        // └─────────────────────────────────────────────────────────────────────┘
        Route::middleware('role:superadmin')->group(function () {
            Route::resource('roles', RoleController::class)->except(['create', 'edit', 'show']);

            Route::post('teachers/{teacher}/link-user', [TeacherController::class, 'linkUser'])->name('teachers.link-user');
            Route::patch('teachers/{teacher}/update-role', [TeacherController::class, 'updateRole'])->name('teachers.update-role');
            Route::delete('teachers/{teacher}/unlink-user', [TeacherController::class, 'unlinkUser'])->name('teachers.unlink-user');
        });

        // ┌─────────────────────────────────────────────────────────────────────┐
        // │  USUARIOS — basado en permisos                                      │
        // └─────────────────────────────────────────────────────────────────────┘
        Route::middleware('permission:users.viewAny')->group(function () {
            Route::resource('users', UserController::class)->except(['create', 'edit']);
        });

        // ┌─────────────────────────────────────────────────────────────────────┐
        // │  BASADO EN PERMISOS — configuración académica y operativa           │
        // └─────────────────────────────────────────────────────────────────────┘

        // Cambio de periodo activo
        Route::middleware('permission:academicPeriods.switch')
            ->post('switch-period', [AcademicPeriodSwitchController::class, 'switch'])
            ->name('switch-period');

        // Docentes — lectura
        Route::middleware('permission:teachers.viewAny')->group(function () {
            Route::get('teachers', [TeacherController::class, 'index'])->name('teachers.index');
            Route::get('teachers/search', [TeacherController::class, 'search'])->name('teachers.search');
            Route::get('teachers/{teacher}', [TeacherController::class, 'show'])->name('teachers.show');
        });

        // Docentes — escritura
        Route::middleware('permission:teachers.update')->group(function () {
            Route::patch('teachers/{teacher}', [TeacherController::class, 'update'])
                ->name('teachers.update');
            Route::patch('teachers/{teacher}/update-email', [TeacherController::class, 'updateEmail'])
                ->name('teachers.update-email');
            Route::get('teachers-template', [TeacherController::class, 'downloadTemplate'])
                ->name('teachers.template');
            Route::post('teachers-import', [TeacherController::class, 'import'])
                ->name('teachers.import');
        });

        // Docentes — crear
        Route::middleware('permission:teachers.create')
            ->post('teachers', [TeacherController::class, 'store'])
            ->name('teachers.store');

        // Campus
        Route::middleware('permission:campus.viewAny')->group(function () {
            Route::resource('campus', CampusController::class);
        });

        // Periodos académicos
        Route::middleware('permission:academicPeriods.viewAny')->group(function () {
            Route::resource('academic-periods', AcademicPeriodController::class)
                ->except(['create', 'edit', 'show']);
        });

        // Facultades
        Route::middleware('permission:faculties.viewAny')->group(function () {
            Route::resource('faculties', FacultyController::class)
                ->except(['create', 'edit', 'show']);
        });

        // Programas
        Route::middleware('permission:programs.viewAny')->group(function () {
            Route::resource('programs', ProgramController::class)
                ->except(['create', 'edit', 'show']);
        });

        // Cursos
        Route::middleware('permission:courses.viewAny')->group(function () {
            Route::resource('courses', CourseController::class)
                ->except(['create', 'edit', 'show']);
        });

        // Importar personas (contactos de docentes)
        Route::middleware('permission:teachers.viewAny')
            ->get('persons', [PersonImportController::class, 'index'])
            ->name('persons.index');

        Route::middleware('permission:teachers.update')->group(function () {
            Route::post('persons/import', [PersonImportController::class, 'import'])
                ->name('persons.import');
            Route::post('persons/quick-store', [PersonImportController::class, 'quickStore'])
                ->name('persons.quick-store');
            Route::post('persons/group', [PersonImportController::class, 'createGroup'])
                ->name('persons.group');
            Route::patch('persons/group/{notificationBatch}', [PersonImportController::class, 'updateGroup'])
                ->name('persons.update-group');
            Route::post('persons/group/{notificationBatch}/concretar', [PersonImportController::class, 'concretarGroup'])
                ->name('persons.concretar-group');
            Route::delete('persons/group/{notificationBatch}', [PersonImportController::class, 'deleteGroup'])
                ->name('persons.delete-group');
            Route::delete('persons/{personImport}', [PersonImportController::class, 'destroy'])
                ->name('persons.destroy');
        });

        // Importaciones Excel
        Route::middleware('permission:excelUploads.viewAny')->group(function () {
            Route::resource('excel-uploads', ExcelUploadController::class)
                ->only(['index', 'store', 'destroy']);
            Route::get('excel-uploads-template', [ExcelUploadController::class, 'downloadTemplate'])
                ->name('excel-uploads.template');
        });

        // Seguimiento — evaluaciones vencidas
        Route::middleware('permission:expiredEvaluations.viewAny')
            ->get('expired-evaluations', [ExpiredEvaluationController::class, 'index'])
            ->name('expired-evaluations.index');

        // Oficinas
        Route::middleware('permission:offices.viewAny')->group(function () {
            Route::resource('offices', OfficeController::class)
                ->except(['create', 'edit', 'show']);
        });

        // Plantillas de notificación
        Route::middleware('permission:notificationTemplates.viewAny')->group(function () {
            Route::get('notification-templates/list', [NotificationBatchController::class, 'getTemplates'])
                ->name('notification-templates.list');
            Route::get('notification-templates/{notificationTemplate}/preview', [NotificationTemplateController::class, 'preview'])
                ->name('notification-templates.preview');
            Route::resource('notification-templates', NotificationTemplateController::class)
                ->except(['create', 'edit']);
        });

        // ┌─────────────────────────────────────────────────────────────────────┐
        // │  CONTACTOS TI — gestión desde página de ayuda                       │
        // └─────────────────────────────────────────────────────────────────────┘
        Route::middleware('permission:itContacts.create')
            ->post('it-contacts', [ItContactController::class, 'store'])
            ->name('it-contacts.store');

        Route::middleware('permission:itContacts.update')
            ->put('it-contacts/{itContact}', [ItContactController::class, 'update'])
            ->name('it-contacts.update');

        Route::middleware('permission:itContacts.delete')
            ->delete('it-contacts/{itContact}', [ItContactController::class, 'destroy'])
            ->name('it-contacts.destroy');

        // Reportes
        Route::middleware('permission:notificationBatches.viewAny')
            ->get('reports', [ReportController::class, 'index'])
            ->name('reports.index');

        // Lotes de notificación — lectura y vista previa
        Route::middleware('permission:notificationBatches.viewAny')->group(function () {
            Route::resource('notification-batches', NotificationBatchController::class)
                ->only(['index', 'show']);
            Route::get('notification-batches/{notificationBatch}/preview', [NotificationBatchController::class, 'preview'])
                ->name('notification-batches.preview');
            Route::delete('notification-batches/{notificationBatch}', [NotificationBatchController::class, 'destroy'])
                ->name('notification-batches.destroy');
        });

        // Lotes — acciones específicas por permiso
        Route::middleware('permission:notificationBatches.attachTemplate')
            ->patch('notification-batches/{notificationBatch}/attach-template', [NotificationBatchController::class, 'attachTemplate'])
            ->name('notification-batches.attach-template');

        Route::middleware('permission:notificationBatches.assignOffice')
            ->patch('notification-batches/{notificationBatch}/assign-office', [NotificationBatchController::class, 'assignOffice'])
            ->name('notification-batches.assign-office');

        Route::middleware('permission:notificationBatches.send')
            ->post('notification-batches/{notificationBatch}/send', [NotificationBatchController::class, 'send'])
            ->name('notification-batches.send');

        Route::middleware('permission:notificationBatches.resend')
            ->post('notification-batch-details/{detail}/resend', [NotificationBatchController::class, 'resendDetail'])
            ->name('notification-batch-details.resend');
    });
