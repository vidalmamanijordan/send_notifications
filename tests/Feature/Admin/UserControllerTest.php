<?php

use App\Models\User;
use Illuminate\Foundation\Http\Middleware\ValidateCsrfToken;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

// ── Setup global para cada test ───────────────────────────────────────────────

beforeEach(function () {
    // CSRF no aplica en tests
    $this->withoutMiddleware(ValidateCsrfToken::class);

    // Limpiar caché de permisos de Spatie
    app()[PermissionRegistrar::class]->forgetCachedPermissions();

    // Crear roles
    Role::firstOrCreate(['name' => 'superadmin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'admin', 'guard_name' => 'web']);
    Role::firstOrCreate(['name' => 'administrativo', 'guard_name' => 'web']);

    // Crear permisos necesarios
    foreach ([
        'users.viewAny', 'users.create', 'users.update', 'users.delete', 'users.assignRole',
    ] as $perm) {
        Permission::firstOrCreate(['name' => $perm, 'guard_name' => 'web']);
    }

    // Superadmin: bypass via Gate::before (no necesita permisos explícitos)
    // Admin y otros: asignar según cada test
});

// ── Helper local ──────────────────────────────────────────────────────────────

function createUserWithRole(string $role): User
{
    $user = User::factory()->create();
    $user->assignRole($role);

    return $user;
}

// ══════════════════════════════════════════════════════════════════════════════
// INDEX
// ══════════════════════════════════════════════════════════════════════════════

it('superadmin can view users index with full permissions', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->has('users')
            ->has('roles')
            ->has('can')
            ->where('isSuperadmin', true)
            ->where('can.create', true)
            ->where('can.update', true)
            ->where('can.delete', true)
        );
});

it('admin with permissions can view users index', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo(['users.viewAny', 'users.create', 'users.update', 'users.delete']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('admin/users/Index')
            ->where('isSuperadmin', false)
            ->where('can.create', true)
            ->where('can.update', true)
            ->where('can.delete', true)
        );
});

it('admin does not see superadmin in available roles list', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo(['users.viewAny']);

    $this->actingAs($admin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('roles', fn ($roles) => ! collect($roles)->contains('name', 'superadmin'))
        );
});

it('superadmin sees all roles including superadmin', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->get(route('admin.users.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->where('roles', fn ($roles) => collect($roles)->contains('name', 'superadmin'))
        );
});

it('unauthenticated user is redirected to login', function () {
    $this->get(route('admin.users.index'))->assertRedirect(route('login'));
});

it('administrativo cannot access users index', function () {
    $administrativo = createUserWithRole('administrativo');

    $this->actingAs($administrativo)
        ->get(route('admin.users.index'))
        ->assertForbidden();
});

it('search filter returns matching users', function () {
    $superadmin = createUserWithRole('superadmin');
    User::factory()->create(['name' => 'Juan Especifico Pérez']);
    User::factory()->create(['name' => 'María López']);

    $this->actingAs($superadmin)
        ->get(route('admin.users.index', ['search' => 'Especifico']))
        ->assertOk()
        ->assertInertia(fn ($page) => $page->where('filters.search', 'Especifico'));
});

// ══════════════════════════════════════════════════════════════════════════════
// STORE
// ══════════════════════════════════════════════════════════════════════════════

it('superadmin can create a user with admin role', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.users.store'), [
            'name' => 'Nuevo Admin',
            'email' => 'nuevo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'nuevo@test.com']);
});

it('superadmin can create a user with superadmin role', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.users.store'), [
            'name' => 'Otro Superadmin',
            'email' => 'superadmin2@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ])
        ->assertRedirect(route('admin.users.index'));

    $created = User::where('email', 'superadmin2@test.com')->firstOrFail();
    expect($created->hasRole('superadmin'))->toBeTrue();
});

it('admin can create a user with administrativo role', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo(['users.viewAny', 'users.create']);

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Nuevo Administrativo',
            'email' => 'admtivo@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'administrativo',
        ])
        ->assertRedirect(route('admin.users.index'));

    $this->assertDatabaseHas('users', ['email' => 'admtivo@test.com']);
});

it('admin cannot create a user with superadmin role', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo('users.create');

    $this->actingAs($admin)
        ->post(route('admin.users.store'), [
            'name' => 'Intento Superadmin',
            'email' => 'intento@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'superadmin',
        ])
        ->assertForbidden();
});

it('store validates required fields', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.users.store'), [])
        ->assertSessionHasErrors(['name', 'email', 'password', 'role']);
});

it('store validates unique email', function () {
    $superadmin = createUserWithRole('superadmin');
    User::factory()->create(['email' => 'existing@test.com']);

    $this->actingAs($superadmin)
        ->post(route('admin.users.store'), [
            'name' => 'Duplicado',
            'email' => 'existing@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors(['email']);
});

it('store validates password minimum length', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->post(route('admin.users.store'), [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => '123',
            'password_confirmation' => '123',
            'role' => 'admin',
        ])
        ->assertSessionHasErrors(['password']);
});

it('user without create permission receives 403 on store', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.viewAny');

    $this->actingAs($user)
        ->post(route('admin.users.store'), [
            'name' => 'Test',
            'email' => 'test@test.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'role' => 'admin',
        ])
        ->assertForbidden();
});

// ══════════════════════════════════════════════════════════════════════════════
// UPDATE
// ══════════════════════════════════════════════════════════════════════════════

it('superadmin can update any user', function () {
    $superadmin = createUserWithRole('superadmin');
    $target = createUserWithRole('admin');

    $this->actingAs($superadmin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Nombre Actualizado',
            'email' => $target->email,
            'role' => 'administrativo',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->name)->toBe('Nombre Actualizado');
    expect($target->fresh()->hasRole('administrativo'))->toBeTrue();
});

it('admin can update a non-superadmin user', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo(['users.viewAny', 'users.update']);
    $target = createUserWithRole('administrativo');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Nombre Nuevo',
            'email' => $target->email,
            'role' => 'administrativo',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->name)->toBe('Nombre Nuevo');
});

it('admin cannot update a superadmin user', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo('users.update');
    $targetSuperadmin = createUserWithRole('superadmin');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $targetSuperadmin), [
            'name' => 'Intento',
            'email' => $targetSuperadmin->email,
            'role' => 'admin',
        ])
        ->assertForbidden();
});

it('admin cannot assign superadmin role on update', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo('users.update');
    $target = createUserWithRole('administrativo');

    $this->actingAs($admin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'superadmin',
        ])
        ->assertForbidden();
});

it('update can change password when provided', function () {
    $superadmin = createUserWithRole('superadmin');
    $target = createUserWithRole('admin');
    $oldPassword = $target->password;

    $this->actingAs($superadmin)
        ->put(route('admin.users.update', $target), [
            'name' => $target->name,
            'email' => $target->email,
            'role' => 'admin',
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->password)->not->toBe($oldPassword);
});

it('update keeps password unchanged when not provided', function () {
    $superadmin = createUserWithRole('superadmin');
    $target = createUserWithRole('admin');
    $originalPassword = $target->password;

    $this->actingAs($superadmin)
        ->put(route('admin.users.update', $target), [
            'name' => 'Nombre Nuevo',
            'email' => $target->email,
            'role' => 'admin',
        ])
        ->assertRedirect(route('admin.users.index'));

    expect($target->fresh()->password)->toBe($originalPassword);
});

it('user without update permission receives 403', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.viewAny');
    $target = createUserWithRole('admin');

    $this->actingAs($user)
        ->put(route('admin.users.update', $target), [
            'name' => 'Test',
            'email' => $target->email,
            'role' => 'admin',
        ])
        ->assertForbidden();
});

// ══════════════════════════════════════════════════════════════════════════════
// DESTROY
// ══════════════════════════════════════════════════════════════════════════════

it('superadmin can delete a non-superadmin user', function () {
    $superadmin = createUserWithRole('superadmin');
    $target = createUserWithRole('admin');

    $this->actingAs($superadmin)
        ->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.users.index'));

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

it('admin can delete a non-superadmin user', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo(['users.viewAny', 'users.delete']);
    $target = createUserWithRole('administrativo');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $target))
        ->assertRedirect(route('admin.users.index'));

    $this->assertSoftDeleted('users', ['id' => $target->id]);
});

it('admin cannot delete a superadmin user', function () {
    $admin = createUserWithRole('admin');
    $admin->givePermissionTo('users.delete');
    $targetSuperadmin = createUserWithRole('superadmin');

    $this->actingAs($admin)
        ->delete(route('admin.users.destroy', $targetSuperadmin))
        ->assertForbidden();
});

it('user cannot delete their own account', function () {
    $superadmin = createUserWithRole('superadmin');

    $this->actingAs($superadmin)
        ->delete(route('admin.users.destroy', $superadmin))
        ->assertForbidden();
});

it('user without delete permission receives 403', function () {
    $user = User::factory()->create();
    $user->givePermissionTo('users.viewAny');
    $target = createUserWithRole('admin');

    $this->actingAs($user)
        ->delete(route('admin.users.destroy', $target))
        ->assertForbidden();
});
