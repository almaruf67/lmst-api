<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows admins to manage teacher accounts and expose classroom options', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $class = config('classroom.classes')[0];
    $section = config('classroom.sections')[0];

    $response = $this->postJson(api('teachers'), [
        'name' => 'Class Teacher',
        'email' => 'teacher@example.com',
        'password' => 'secret123',
        'phone' => '01700000002',
        'class_name' => $class,
        'section' => $section,
        'employee_code' => 'TCH-9001',
        'subject_specialization' => 'Mathematics',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.class_name', $class)
        ->assertJsonPath('data.section', $section);

    $createdTeacher = $response->json('data.id');

    $optionsResponse = $this->getJson(api('teachers/options'));
    $optionsResponse->assertOk()
        ->assertExactJson([
            'message' => 'Classroom options',
            'data' => [
                'classes' => config('classroom.classes'),
                'sections' => config('classroom.sections'),
            ],
            'version' => 'v1',
        ]);

    $listResponse = $this->getJson(api("teachers?class_name={$class}&section={$section}"));
    $listResponse->assertOk();

    expect(collect($listResponse->json('data.users'))->pluck('id'))->toContain($createdTeacher);

    $deleteResponse = $this->deleteJson(api("teachers/{$createdTeacher}"));
    $deleteResponse->assertOk();

    $this->assertSoftDeleted('users', ['id' => $createdTeacher]);
});

it('allows teachers to manage only their own profile', function (): void {
    $teacherA = User::factory()->teacher('Class 1', 'A')->create([
        'email' => 'first-teacher@example.com',
    ]);
    $teacherB = User::factory()->teacher('Class 2', 'B')->create();

    Sanctum::actingAs($teacherA);

    $this->getJson(api('teachers'))->assertForbidden();
    $this->postJson(api('teachers'), [
        'name' => 'Blocked Teacher',
        'email' => 'blocked-teacher@example.com',
        'password' => 'secret123',
        'class_name' => 'Class 1',
        'section' => 'A',
        'employee_code' => 'TCH-9999',
    ])->assertForbidden();

    $this->getJson(api("teachers/{$teacherA->id}"))
        ->assertOk()
        ->assertJsonPath('data.email', 'first-teacher@example.com');

    $this->getJson(api("teachers/{$teacherB->id}"))
        ->assertForbidden();

    $this->putJson(api("teachers/{$teacherA->id}"), [
        'phone' => '01700000077',
    ])->assertOk()
        ->assertJsonPath('data.phone', '01700000077');

    $this->putJson(api("teachers/{$teacherB->id}"), [
        'phone' => '01700000099',
    ])->assertForbidden();
});

it('validates classroom options when creating teachers', function (): void {
    $admin = User::factory()->admin()->create();
    Sanctum::actingAs($admin);

    $response = $this->postJson(api('teachers'), [
        'name' => 'Invalid Teacher',
        'email' => 'invalid-teacher@example.com',
        'password' => 'secret123',
        'class_name' => 'Unknown Class',
        'section' => 'Z',
        'employee_code' => 'TCH-0001',
    ]);

    $response->assertUnprocessable()
        ->assertJsonPath('data.class_name.0', 'The selected class name is invalid.')
        ->assertJsonPath('data.section.0', 'The selected section is invalid.');
});
