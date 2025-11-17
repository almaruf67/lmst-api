<?php

declare(strict_types=1);

namespace App\Services\Profile;

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class ProfileService
{
    /**
     * Update the base profile fields for the authenticated user.
     *
     * @param  array<string, mixed>  $payload
     */
    public function update(User $user, array $payload): User
    {
        $allowed = Arr::only($payload, [
            'name',
            'email',
            'phone',
            'class_name',
            'section',
        ]);

        $user->fill($allowed);
        $user->save();

        return $user->fresh();
    }

    /**
     * Update password after verifying the existing credential.
     */
    public function updatePassword(User $user, string $currentPassword, string $newPassword): void
    {
        if (! Hash::check($currentPassword, $user->password)) {
            throw ValidationException::withMessages([
                'current_password' => ['The provided password does not match our records.'],
            ]);
        }

        $user->password = $newPassword;
        $user->save();
    }

    /**
     * Update the profile avatar for the user.
     */
    public function updateAvatar(User $user, UploadedFile $avatar): User
    {
        $directory = 'avatars/'.$user->getKey();
        $filename = uniqid('avatar_', true).'.'.$avatar->getClientOriginalExtension();

        $path = $avatar->storeAs($directory, $filename, 'public');

        if ($user->profile_photo_path && Storage::disk('public')->exists($user->profile_photo_path)) {
            Storage::disk('public')->delete($user->profile_photo_path);
        }

        $user->profile_photo_path = $path;
        $user->save();

        return $user->fresh();
    }
}
