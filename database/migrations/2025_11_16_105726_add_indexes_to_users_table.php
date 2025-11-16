<?php

declare(strict_types=1);

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
            if (Schema::hasColumn('users', 'user_type')) {
                $table->index('user_type', 'users_user_type_index');
            }

            if (Schema::hasColumn('users', 'class_name')) {
                $table->index('class_name', 'users_class_name_index');
            }

            if (Schema::hasColumn('users', 'section')) {
                $table->index('section', 'users_section_index');
            }

            if (Schema::hasColumn('users', 'class_name') && Schema::hasColumn('users', 'section')) {
                $table->index(['class_name', 'section'], 'users_class_section_index');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            if (Schema::hasColumn('users', 'user_type')) {
                $table->dropIndex('users_user_type_index');
            }

            if (Schema::hasColumn('users', 'class_name')) {
                $table->dropIndex('users_class_name_index');
            }

            if (Schema::hasColumn('users', 'section')) {
                $table->dropIndex('users_section_index');
            }

            if (Schema::hasColumn('users', 'class_name') && Schema::hasColumn('users', 'section')) {
                $table->dropIndex('users_class_section_index');
            }
        });
    }
};
