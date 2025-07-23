<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_types', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // Weekly Teaching Plan, Final Exams, Meetings.
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->boolean('is_repetitive')->default(true); // Weekly = true, Final Exams = false
            $table->enum('repetition_pattern', ['daily', 'weekly', 'monthly', 'none'])->default('weekly');
            $table->json('default_settings')->nullable(); // Default duration, break times, etc.
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        // Create schedules table
        Schema::create('schedules', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->string('code')->unique();
            $table->foreignId('schedule_type_id')->constrained()->cascadeOnDelete();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete();
            $table->text('description')->nullable();
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->time('day_starts_at')->default('09:00:00');
            $table->time('day_ends_at')->default('15:40:00'); 
            $table->integer('slot_duration_minutes')->default(50);
            $table->integer('break_duration_minutes')->default(0);
            $table->json('settings')->nullable();
            $table->enum('status', ['draft', 'active', 'finalized', 'archived'])->default('draft');
            $table->timestamp('finalized_at')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedules');
        Schema::dropIfExists('schedule_types');
    }
};