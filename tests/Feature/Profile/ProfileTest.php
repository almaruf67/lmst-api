<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Filesystem\FilesystemAdapter;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(RefreshDatabase::class);

test('it returns the authenticated profile details', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create([
        'name' => 'Admin User',
        'email' => 'admin@example.com',
    ]);

    Sanctum::actingAs($user);

    $response = $this->getJson(api('profile'));

    $response->assertOk()
        ->assertJsonPath('data.email', 'admin@example.com')
        ->assertJsonPath('data.name', 'Admin User');
});

test('it updates personal information', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->teacher()->create([
        'name' => 'Original Name',
        'email' => 'teacher@example.com',
        'phone' => '01700000000',
        'class_name' => 'Grade 5',
        'section' => 'A',
    ]);

    Sanctum::actingAs($user);

    $payload = [
        'name' => 'Updated Teacher',
        'email' => 'teacher.updated@example.com',
        'phone' => '01800000000',
        'class_name' => 'Grade 6',
        'section' => 'B',
    ];

    $response = $this->putJson(api('profile'), $payload);

    $response->assertOk()
        ->assertJsonPath('data.name', 'Updated Teacher')
        ->assertJsonPath('data.email', 'teacher.updated@example.com');

    $this->assertDatabaseHas('users', array_merge(['id' => $user->id], $payload));
});

test('it updates the avatar image', function (): void {
    /** @var TestCase $this */
    Storage::fake('public');

    $user = User::factory()->admin()->create();
    Sanctum::actingAs($user);

    $file = UploadedFile::fake()->image('avatar.jpg');

    $response = $this->postJson(api('profile/avatar'), [
        'avatar' => $file,
    ]);

    $response->assertOk();

    $fresh = $user->fresh();
    expect($fresh->profile_photo_path)->not->toBeNull();

    /** @var FilesystemAdapter $disk */
    $disk = Storage::disk('public');
    $disk->assertExists($fresh->profile_photo_path);
});

test('it changes the password when current password matches', function (): void {
    /** @var TestCase $this */
    $user = User::factory()->admin()->create([
        'password' => Hash::make('secret123'),
    ]);

    Sanctum::actingAs($user);

    $response = $this->putJson(api('profile/password'), [
        'current_password' => 'secret123',
        'password' => 'new-secret-456',
        'password_confirmation' => 'new-secret-456',
    ]);

    $response->assertOk();

    expect(Hash::check('new-secret-456', $user->fresh()->password))->toBeTrue();
});
