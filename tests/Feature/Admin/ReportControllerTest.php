<?php

use App\Models\User;

beforeEach(function () {
    $this->admin = User::factory()->create();
    $this->admin->givePermissionTo('notificationBatches.viewAny');
});

it('renders the reports index page', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.index'));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->component('admin/reports/Index')
        ->has('filters')
        ->has('campusList')
        ->has('facultyList')
        ->has('programList')
        ->has('topTeachersCycle')
        ->has('byCampusNotifications')
        ->has('byFacultyNotifications')
        ->has('byProgramNotifications')
        ->has('teacherDetailReport')
    );
});

it('accepts cycle campus filter', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.index', ['cycle_campus_id' => 1]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.cycle_campus_id', 1)
    );
});

it('accepts cycle faculty and program filters', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.index', [
            'cycle_campus_id' => 1,
            'cycle_faculty_id' => 2,
            'cycle_program_id' => 3,
        ]));

    $response->assertSuccessful();
    $response->assertInertia(fn ($page) => $page
        ->where('filters.cycle_campus_id', 1)
        ->where('filters.cycle_faculty_id', 2)
        ->where('filters.cycle_program_id', 3)
    );
});

it('returns faculty and program lists for filter dropdowns', function () {
    $response = $this->actingAs($this->admin)
        ->get(route('admin.reports.index'));

    $response->assertInertia(fn ($page) => $page
        ->has('facultyList')
        ->has('programList')
    );
});
