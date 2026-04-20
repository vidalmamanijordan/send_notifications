<?php

use App\Models\Teacher;
use App\Models\User;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    Permission::firstOrCreate(['name' => 'teachers.create', 'guard_name' => 'web']);
    Permission::firstOrCreate(['name' => 'teachers.update', 'guard_name' => 'web']);

    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo(['teachers.create', 'teachers.update']);

    $this->withoutMiddleware(\Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class);
});

it('stores phone when creating a teacher', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'dni' => '12345678',
            'full_name' => 'Juan Pérez',
            'phone' => '987654321',
        ]);

    $response->assertRedirect();

    $teacher = Teacher::where('dni', '12345678')->first();
    expect($teacher)->not->toBeNull();
    expect($teacher->phone)->toBe('987654321');
});

it('allows creating a teacher without phone', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'dni' => '87654321',
            'full_name' => 'María García',
        ]);

    $response->assertRedirect();

    $teacher = Teacher::where('dni', '87654321')->first();
    expect($teacher)->not->toBeNull();
    expect($teacher->phone)->toBeNull();
});

it('updates phone when editing a teacher', function () {
    $teacher = Teacher::create([
        'dni' => '11223344',
        'full_name' => 'Carlos López',
        'email' => null,
        'phone' => null,
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.teachers.update', $teacher), [
            'full_name' => 'Carlos López',
            'email' => null,
            'phone' => '912345678',
            'is_active' => true,
        ]);

    $response->assertRedirect();

    expect($teacher->fresh()->phone)->toBe('912345678');
});

it('rejects phone exceeding max length', function () {
    $response = $this->actingAs($this->admin)
        ->post(route('admin.teachers.store'), [
            'dni' => '55667788',
            'full_name' => 'Ana Torres',
            'phone' => str_repeat('9', 21),
        ]);

    $response->assertSessionHasErrors('phone');
});
