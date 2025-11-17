<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const STUDENTS_CLASS_INDEX = 'students_class_name_idx';

    private const STUDENTS_SECTION_INDEX = 'students_section_idx';

    private const ATTENDANCE_DATE_INDEX = 'attendances_date_idx';

    private const ATTENDANCE_STATUS_INDEX = 'attendances_status_idx';

    private const ATTENDANCE_STUDENT_INDEX = 'attendances_student_idx';

    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table): void {
            $table->index('class_name', self::STUDENTS_CLASS_INDEX);
            $table->index('section', self::STUDENTS_SECTION_INDEX);
        });

        Schema::table('attendances', function (Blueprint $table): void {
            $table->index('attendance_date', self::ATTENDANCE_DATE_INDEX);
            $table->index('status', self::ATTENDANCE_STATUS_INDEX);
            $table->index('student_id', self::ATTENDANCE_STUDENT_INDEX);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table): void {
            $table->dropIndex(self::ATTENDANCE_DATE_INDEX);
            $table->dropIndex(self::ATTENDANCE_STATUS_INDEX);
            $table->dropIndex(self::ATTENDANCE_STUDENT_INDEX);
        });

        Schema::table('students', function (Blueprint $table): void {
            $table->dropIndex(self::STUDENTS_CLASS_INDEX);
            $table->dropIndex(self::STUDENTS_SECTION_INDEX);
        });
    }
};
