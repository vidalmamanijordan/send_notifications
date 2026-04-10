<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    /** Roles del sistema que no pueden eliminarse ni renombrarse. */
    private const SYSTEM_ROLES = ['superadmin', 'admin', 'administrativo'];

    public function index(): \Inertia\Response
    {
        $roles = Role::withCount('users')
            ->with('permissions:id,name')
            ->orderBy('name')
            ->get(['id', 'name']);

        $permissions = Permission::orderBy('name')
            ->get(['id', 'name'])
            ->groupBy(fn (Permission $p) => explode('.', $p->name)[0]);

        return Inertia::render('admin/roles/Index', [
            'roles' => $roles,
            'permissions' => $permissions,
            'systemRoles' => self::SYSTEM_ROLES,
        ]);
    }

    public function store(Request $request): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:64|unique:roles,name|alpha_dash',
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        $role = Role::create(['name' => $validated['name']]);
        $role->syncPermissions($validated['permissions'] ?? []);

        return back()->with('success', "Rol \"{$role->name}\" creado correctamente.");
    }

    public function update(Request $request, Role $role): \Illuminate\Http\RedirectResponse
    {
        $validated = $request->validate([
            'name' => 'required|string|max:64|alpha_dash|unique:roles,name,'.$role->id,
            'permissions' => 'array',
            'permissions.*' => 'string|exists:permissions,name',
        ]);

        // Superadmin siempre mantiene todos los permisos
        if ($role->name === 'superadmin') {
            $role->syncPermissions(Permission::all());
        } else {
            $role->syncPermissions($validated['permissions'] ?? []);
        }

        if ($role->name !== $validated['name']) {
            abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'No se puede renombrar un rol del sistema.');
            $role->update(['name' => $validated['name']]);
        }

        return back()->with('success', "Rol \"{$role->name}\" actualizado correctamente.");
    }

    public function destroy(Role $role): \Illuminate\Http\RedirectResponse
    {
        abort_if(in_array($role->name, self::SYSTEM_ROLES, true), 403, 'No se puede eliminar un rol del sistema.');

        $role->delete();

        return back()->with('success', 'Rol eliminado correctamente.');
    }
}
