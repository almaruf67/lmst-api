<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@test.com'],
            [
                'name' => 'System Administrator',
                'password' => Hash::make('password'),
                'user_type' => UserType::Admin,
                'phone' => '+1-202-555-0101',
            ]
        );

        $teachers = [
            ['name' => 'Class 1A Teacher', 'email' => 'teacher1@test.com', 'class_name' => 'Class 1', 'section' => 'A', 'subject' => 'Mathematics'],
            ['name' => 'Class 2B Teacher', 'email' => 'teacher2@test.com', 'class_name' => 'Class 2', 'section' => 'B', 'subject' => 'Science'],
            ['name' => 'Class 3C Teacher', 'email' => 'teacher3@test.com', 'class_name' => 'Class 3', 'section' => 'C', 'subject' => 'English'],
            ['name' => 'Class 4A Teacher', 'email' => 'teacher4@test.com', 'class_name' => 'Class 4', 'section' => 'A', 'subject' => 'History'],
            ['name' => 'Class 5B Teacher', 'email' => 'teacher5@test.com', 'class_name' => 'Class 5', 'section' => 'B', 'subject' => 'Geography'],
        ];

        foreach ($teachers as $index => $teacher) {
            User::query()->updateOrCreate(
                ['email' => $teacher['email']],
                [
                    'name' => $teacher['name'],
                    'password' => Hash::make('password'),
                    'user_type' => UserType::Teacher,
                    'class_name' => $teacher['class_name'],
                    'section' => $teacher['section'],
                    'subject_specialization' => $teacher['subject'],
                    'employee_code' => sprintf('EMP-%03d', $index + 1),
                    'phone' => sprintf('+1-202-555-%04d', 1200 + $index),
                ]
            );
        }
    }
}
