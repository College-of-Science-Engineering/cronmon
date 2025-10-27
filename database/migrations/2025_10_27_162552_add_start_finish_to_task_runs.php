<?php

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
        Schema::table('task_runs', function (Blueprint $table) {
            $table->timestamp('started_at')->nullable()->after('checked_in_at');
            $table->timestamp('finished_at')->nullable()->after('started_at');
            $table->integer('execution_time_seconds')->nullable()->after('finished_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('task_runs', function (Blueprint $table) {
            $table->dropColumn(['started_at', 'finished_at', 'execution_time_seconds']);
        });
    }
};
