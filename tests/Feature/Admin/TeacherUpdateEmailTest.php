<?php

use App\Models\Teacher;
use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('teachers.update');
});

it('updates the teacher email successfully', function () {
    $teacher = Teacher::create([
        'dni' => '12345678',
        'full_name' => 'Juan Pérez',
        'email' => 'old@example.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.teachers.update-email', $teacher), [
            'email' => 'new@example.com',
        ]);

    $response->assertRedirect();

    expect($teacher->fresh()->email)->toBe('new@example.com');
});

it('allows clearing the teacher email to null', function () {
    $teacher = Teacher::create([
        'dni' => '87654321',
        'full_name' => 'María García',
        'email' => 'maria@example.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.teachers.update-email', $teacher), [
            'email' => null,
        ]);

    $response->assertRedirect();

    expect($teacher->fresh()->email)->toBeNull();
});

it('rejects an invalid email format', function () {
    $teacher = Teacher::create([
        'dni' => '11223344',
        'full_name' => 'Carlos López',
        'email' => 'valid@example.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($this->admin)
        ->patch(route('admin.teachers.update-email', $teacher), [
            'email' => 'not-an-email',
        ]);

    $response->assertSessionHasErrors('email');
    expect($teacher->fresh()->email)->toBe('valid@example.com');
});

it('also updates the linked user email when teacher email changes', function () {
    $user = User::factory()->create(['email' => 'user-old@example.com']);
    $teacher = Teacher::create([
        'dni' => '55667788',
        'full_name' => 'Ana Torres',
        'email' => 'user-old@example.com',
        'is_active' => true,
        'user_id' => $user->id,
    ]);

    $this->actingAs($this->admin)
        ->patch(route('admin.teachers.update-email', $teacher), [
            'email' => 'user-new@example.com',
        ]);

    expect($teacher->fresh()->email)->toBe('user-new@example.com');
    expect($user->fresh()->email)->toBe('user-new@example.com');
});

it('requires teachers.update permission', function () {
    $guest = User::factory()->create();
    $teacher = Teacher::create([
        'dni' => '99887766',
        'full_name' => 'Pedro Ruiz',
        'email' => 'pedro@example.com',
        'is_active' => true,
    ]);

    $response = $this->actingAs($guest)
        ->patch(route('admin.teachers.update-email', $teacher), [
            'email' => 'hacked@example.com',
        ]);

    $response->assertForbidden();
    expect($teacher->fresh()->email)->toBe('pedro@example.com');
});
