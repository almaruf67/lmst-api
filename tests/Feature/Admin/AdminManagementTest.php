<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('allows admins to manage admin accounts and prevents self deletion', function (): void {
    $authAdmin = User::factory()->admin()->create([
        'email' => 'owner@example.com',
    ]);

    Sanctum::actingAs($authAdmin);

    $response = $this->postJson(api('admins'), [
        'name' => 'Principal Admin',
        'email' => 'principal@example.com',
        'password' => 'secret123',
        'phone' => '01700000001',
        'employee_code' => 'ADM-1001',
    ]);

    $response->assertCreated()
        ->assertJsonPath('data.name', 'Principal Admin');

    $createdId = $response->json('data.id');

    $listResponse = $this->getJson(api('admins'));
    $listResponse->assertOk();

    expect(collect($listResponse->json('data.users'))->pluck('id'))->toContain($createdId);

    $updateResponse = $this->putJson(api("admins/{$createdId}"), [
        'name' => 'Updated Admin',
        'password' => 'newsecret123',
    ]);

    $updateResponse->assertOk()
        ->assertJsonPath('data.name', 'Updated Admin');

    $selfDeleteResponse = $this->deleteJson(api("admins/{$authAdmin->id}"));

    $selfDeleteResponse->assertUnprocessable()
        ->assertJsonPath('message', 'You cannot delete your own account.');

    $deleteResponse = $this->deleteJson(api("admins/{$createdId}"));

    $deleteResponse->assertOk();

    $this->assertSoftDeleted('users', [
        'id' => $createdId,
    ]);
});

it('blocks non-admins from accessing admin endpoints', function (): void {
    $teacher = User::factory()->teacher()->create([
        'user_type' => UserType::Teacher->value,
    ]);

    Sanctum::actingAs($teacher);

    $this->getJson(api('admins'))->assertForbidden();
    $this->postJson(api('admins'), [
        'name' => 'Blocked',
        'email' => 'blocked@example.com',
        'password' => 'secret123',
        'employee_code' => 'ADM-2000',
    ])->assertForbidden();
});
