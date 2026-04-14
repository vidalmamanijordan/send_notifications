<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RolePermissionSeeder extends Seeder
{
    public function run(): void
    {
        // Limpiar caché de permisos
        app()[PermissionRegistrar::class]->forgetCachedPermissions();

        // ── Permisos ─────────────────────────────────────────────────────────

        $permissions = [
            // Usuarios
            'users.viewAny',
            'users.create',
            'users.update',
            'users.delete',
            'users.assignRole',

            // Campus
            'campus.viewAny',
            'campus.create',
            'campus.update',
            'campus.delete',

            // Periodos académicos
            'academicPeriods.viewAny',
            'academicPeriods.create',
            'academicPeriods.update',
            'academicPeriods.delete',
            'academicPeriods.switch',

            // Facultades
            'faculties.viewAny',
            'faculties.create',
            'faculties.update',
            'faculties.delete',

            // Programas
            'programs.viewAny',
            'programs.create',
            'programs.update',
            'programs.delete',

            // Cursos
            'courses.viewAny',
            'courses.create',
            'courses.update',
            'courses.delete',

            // Docentes
            'teachers.viewAny',
            'teachers.create',
            'teachers.update',

            // Cargas Excel
            'excelUploads.viewAny',
            'excelUploads.create',
            'excelUploads.delete',

            // Evaluaciones vencidas
            'expiredEvaluations.viewAny',

            // Oficinas
            'offices.viewAny',
            'offices.create',
            'offices.update',
            'offices.delete',

            // Plantillas de notificación
            'notificationTemplates.viewAny',
            'notificationTemplates.create',
            'notificationTemplates.update',
            'notificationTemplates.delete',

            // Lotes de notificación
            'notificationBatches.viewAny',
            'notificationBatches.show',
            'notificationBatches.attachTemplate',
            'notificationBatches.assignOffice',
            'notificationBatches.send',
            'notificationBatches.resend',
        ];

        foreach ($permissions as $perm) {
            Permission::firstOrCreate(['name' => $perm]);
        }

        // ── Roles ─────────────────────────────────────────────────────────────

        // 1. Superadmin — acceso total (bypass via Gate::before en AuthServiceProvider)
        $superadmin = Role::firstOrCreate(['name' => 'superadmin']);
        $superadmin->syncPermissions(Permission::all());

        // 2. Admin — todo excepto gestión de usuarios y asignación de roles
        $admin = Role::firstOrCreate(['name' => 'admin']);
        $adminPermissions = Permission::whereNotIn('name', [
            'users.viewAny',
            'users.create',
            'users.update',
            'users.delete',
            'users.assignRole',
        ])->get();
        $admin->syncPermissions($adminPermissions);

        // 3. Administrativo — gestión operativa de notificaciones
        $administrativo = Role::firstOrCreate(['name' => 'administrativo']);
        $administrativo->syncPermissions([
            'academicPeriods.switch',
            'teachers.viewAny',
            'expiredEvaluations.viewAny',
            'offices.viewAny',
            'notificationTemplates.viewAny',
            'notificationBatches.viewAny',
            'notificationBatches.show',
            'notificationBatches.attachTemplate',
            'notificationBatches.assignOffice',
            'notificationBatches.send',
            'notificationBatches.resend',
        ]);

        $this->command->info('✅ Roles y permisos creados correctamente.');
        $this->command->table(
            ['Rol', 'Permisos'],
            [
                ['superadmin',    $superadmin->permissions->count()],
                ['admin',         $admin->permissions->count()],
                ['administrativo', $administrativo->permissions->count()],
            ]
        );
    }
}
