<?php

declare(strict_types=1);

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('returns dashboard summary for admin', function (): void {
    $admin = User::factory()->create([
        'user_type' => UserType::Admin->value,
    ]);

    Sanctum::actingAs($admin);

    $response = $this->getJson(api('dashboard/summary'));

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => ['date', 'total', 'totals_by_status', 'present_percentage'],
            'version',
        ]);
});

it('returns dashboard summary for teacher scope', function (): void {
    $teacher = User::factory()->teacher()->create();

    Sanctum::actingAs($teacher);

    $response = $this->getJson(api('dashboard/summary'));

    $response->assertOk()
        ->assertJsonStructure([
            'message',
            'data' => ['date', 'total', 'totals_by_status', 'present_percentage'],
            'version',
        ]);
});
