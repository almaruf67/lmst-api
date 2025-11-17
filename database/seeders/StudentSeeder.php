<?php

declare(strict_types=1);

namespace Database\Seeders;

use App\Enums\UserType;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class StudentSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cohorts = [
            ['class_name' => 'Class 1', 'section' => 'A', 'count' => 10],
            ['class_name' => 'Class 2', 'section' => 'B', 'count' => 10],
            ['class_name' => 'Class 3', 'section' => 'C', 'count' => 10],
            ['class_name' => 'Class 4', 'section' => 'A', 'count' => 10],
            ['class_name' => 'Class 5', 'section' => 'B', 'count' => 10],
        ];

        $teachers = User::query()
            ->where('user_type', UserType::Teacher)
            ->get()
            ->keyBy(fn (User $teacher): string => $this->assignmentKey($teacher->class_name, $teacher->section));

        foreach ($cohorts as $cohort) {
            $existingCount = Student::query()
                ->where('class_name', $cohort['class_name'])
                ->where('section', $cohort['section'])
                ->count();

            $needed = $cohort['count'] - $existingCount;

            if ($needed <= 0) {
                continue;
            }

            $teacher = $teachers->get($this->assignmentKey($cohort['class_name'], $cohort['section']));

            Student::factory()
                ->count($needed)
                ->state(fn (array $attributes): array => [
                    'class_name' => $cohort['class_name'],
                    'section' => $cohort['section'],
                    'primary_teacher_id' => $teacher?->getKey(),
                ])
                ->create();
        }
    }

    private function assignmentKey(?string $className, ?string $section): string
    {
        return sprintf('%s|%s', $className ?? '-', $section ?? '-');
    }
}
