<?php

use App\Models\User;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'prefix' => 'api',
    'middleware' => ['api', 'auth:sanctum'],
]);

Broadcast::channel('users.{id}', function (User $user, int $id): bool {
    return (int) $user->getKey() === $id;
});

Broadcast::channel('notifications.user.{id}', function (User $user, int $id): bool {
    return (int) $user->getKey() === $id;
});
