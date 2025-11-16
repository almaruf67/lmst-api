<?php

declare(strict_types=1);

use App\Enums\UserType;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (! Schema::hasColumn('users', 'user_type')) {
                $table->string('user_type')->default(UserType::Teacher->value)->after('password');
            }

            if (! Schema::hasColumn('users', 'phone')) {
                $table->string('phone', 20)->nullable()->after('user_type');
            }

            if (! Schema::hasColumn('users', 'class_name')) {
                $table->string('class_name')->nullable()->after('phone');
            }

            if (! Schema::hasColumn('users', 'section')) {
                $table->string('section')->nullable()->after('class_name');
            }

            if (! Schema::hasColumn('users', 'employee_code')) {
                $table->string('employee_code')->nullable()->unique()->after('section');
            }

            if (! Schema::hasColumn('users', 'subject_specialization')) {
                $table->string('subject_specialization')->nullable()->after('employee_code');
            }

            if (! Schema::hasColumn('users', 'qualification')) {
                $table->string('qualification')->nullable()->after('subject_specialization');
            }

            if (! Schema::hasColumn('users', 'date_of_joining')) {
                $table->date('date_of_joining')->nullable()->after('qualification');
            }

            if (! Schema::hasColumn('users', 'emergency_contact_name')) {
                $table->string('emergency_contact_name')->nullable()->after('date_of_joining');
            }

            if (! Schema::hasColumn('users', 'emergency_contact_phone')) {
                $table->string('emergency_contact_phone', 20)->nullable()->after('emergency_contact_name');
            }

            if (! Schema::hasColumn('users', 'created_by')) {
                $table->foreignId('created_by')->nullable()->after('emergency_contact_phone')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'updated_by')) {
                $table->foreignId('updated_by')->nullable()->after('created_by')->constrained('users')->nullOnDelete();
            }

            if (! Schema::hasColumn('users', 'created_ip')) {
                $table->string('created_ip', 45)->nullable()->after('updated_by');
            }

            if (! Schema::hasColumn('users', 'updated_ip')) {
                $table->string('updated_ip', 45)->nullable()->after('created_ip');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $columns = [
                'user_type',
                'phone',
                'class_name',
                'section',
                'employee_code',
                'subject_specialization',
                'qualification',
                'date_of_joining',
                'emergency_contact_name',
                'emergency_contact_phone',
                'created_by',
                'updated_by',
                'created_ip',
                'updated_ip',
            ];

            foreach ($columns as $column) {
                if (Schema::hasColumn('users', $column)) {
                    if (in_array($column, ['created_by', 'updated_by'], true)) {
                        $table->dropConstrainedForeignId($column);
                    } else {
                        $table->dropColumn($column);
                    }
                }
            }
        });
    }
};
