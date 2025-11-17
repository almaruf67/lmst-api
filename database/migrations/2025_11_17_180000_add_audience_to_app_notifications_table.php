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
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->string('audience', 30)->default('teacher')->after('priority');
            $table->index(['user_id', 'audience']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('app_notifications', function (Blueprint $table): void {
            $table->dropIndex('app_notifications_user_id_audience_index');
            $table->dropColumn('audience');
        });
    }
};
