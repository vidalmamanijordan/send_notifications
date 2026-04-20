<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rules\Password;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->input('search');
        $viewer = auth()->user();
        $viewerIsSuperadmin = $viewer->hasRole('superadmin');

        $users = User::select('id', 'name', 'email', 'email_verified_at', 'created_at')
            ->with('roles:id,name')
            ->when($search, function ($query, $search) {
                $query->where(function ($q) use ($search) {
                    $q->where('name', 'like', "%{$search}%")
                        ->orWhere('email', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        // Admin solo puede ver / asignar roles distintos de superadmin
        $availableRoles = $viewerIsSuperadmin
            ? Role::orderBy('name')->get(['id', 'name'])
            : Role::where('name', '!=', 'superadmin')->orderBy('name')->get(['id', 'name']);

        return Inertia::render('admin/users/Index', [
            'users' => $users,
            'filters' => ['search' => $search],
            'roles' => $availableRoles,
            'can' => [
                'create' => $viewer->can('users.create'),
                'update' => $viewer->can('users.update'),
                'delete' => $viewer->can('users.delete'),
            ],
            'isSuperadmin' => $viewerIsSuperadmin,
        ]);
    }

    public function store(Request $request)
    {
        abort_if(! auth()->user()->can('users.create'), 403);

        $viewer = auth()->user();

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => ['required', 'confirmed', Password::min(8)],
            'role' => 'required|string|exists:roles,name',
        ]);

        // Admin no puede asignar el rol superadmin
        abort_if(
            ! $viewer->hasRole('superadmin') && $validated['role'] === 'superadmin',
            403,
            'No tienes permiso para asignar el rol superadmin.'
        );

        $user = User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => $validated['password'],
        ]);

        $user->syncRoles($validated['role']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario creado correctamente.');
    }

    public function update(Request $request, User $user)
    {
        abort_if(! auth()->user()->can('users.update'), 403);

        $viewer = auth()->user();

        // Admin no puede editar usuarios superadmin
        abort_if(
            ! $viewer->hasRole('superadmin') && $user->hasRole('superadmin'),
            403,
            'No tienes permiso para editar usuarios superadmin.'
        );

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => ['nullable', 'confirmed', Password::min(8)],
            'role' => 'required|string|exists:roles,name',
        ]);

        // Admin no puede asignar el rol superadmin
        abort_if(
            ! $viewer->hasRole('superadmin') && $validated['role'] === 'superadmin',
            403,
            'No tienes permiso para asignar el rol superadmin.'
        );

        $data = [
            'name' => $validated['name'],
            'email' => $validated['email'],
        ];

        if (! empty($validated['password'])) {
            $data['password'] = $validated['password'];
        }

        $user->update($data);
        $user->syncRoles($validated['role']);

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario actualizado correctamente.');
    }

    public function destroy(User $user)
    {
        abort_if(! auth()->user()->can('users.delete'), 403);
        abort_if($user->id === auth()->id(), 403, 'No puedes eliminar tu propia cuenta.');

        $viewer = auth()->user();

        // Admin no puede eliminar usuarios superadmin
        abort_if(
            ! $viewer->hasRole('superadmin') && $user->hasRole('superadmin'),
            403,
            'No tienes permiso para eliminar usuarios superadmin.'
        );

        $user->delete();

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Usuario eliminado correctamente.');
    }
}
