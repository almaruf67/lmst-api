<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TeacherAndStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $teacher = User::query()->updateOrCreate(
            ['email' => 'teacher@example.com'],
            [
                'name' => 'Class 1A Teacher',
                'password' => Hash::make('password'),
                'user_type' => UserType::Teacher,
                'class_name' => 'Class 1',
                'section' => 'A',
            ]
        );

        // Create students for Class 1A
        if (! Student::query()->where('class_name', 'Class 1')->where('section', 'A')->exists()) {
            Student::factory()->count(15)->create([
                'class_name' => 'Class 1',
                'section' => 'A',
                'primary_teacher_id' => $teacher->getKey(),
            ]);
        }
    }
}
