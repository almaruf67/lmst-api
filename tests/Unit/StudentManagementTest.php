<?php

declare(strict_types=1);

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('detects admin and teacher helpers', function (): void {
    $admin = User::factory()->admin()->make();

    expect($admin->isAdmin())->toBeTrue()
        ->and($admin->isTeacher())->toBeFalse();

    $teacher = User::factory()->teacher('Grade 8', 'B')->make();

    expect($teacher->isTeacher())->toBeTrue()
        ->and($teacher->isAdmin())->toBeFalse();
});
