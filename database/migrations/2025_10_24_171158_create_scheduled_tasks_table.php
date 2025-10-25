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
        Schema::create('scheduled_tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('schedule_type', ['simple', 'cron']);
            $table->string('schedule_value');
            $table->string('timezone')->default('UTC');
            $table->integer('grace_period_minutes')->default(10);
            $table->string('unique_check_in_token')->unique();
            $table->timestamp('last_checked_in_at')->nullable();
            $table->timestamp('next_expected_at')->nullable();
            $table->enum('status', ['ok', 'pending', 'alerting', 'paused'])->default('pending');
            $table->timestamps();

            $table->index('unique_check_in_token');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('scheduled_tasks');
    }
};
