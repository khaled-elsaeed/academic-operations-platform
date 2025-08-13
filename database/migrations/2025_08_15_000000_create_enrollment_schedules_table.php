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
        Schema::create('enrollment_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('available_course_schedule_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['active', 'dropped', 'completed'])->default('active');
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['enrollment_id', 'available_course_schedule_id'], 'unique_enrollment_schedule');
            
            // Index for better performance
            $table->index(['enrollment_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('enrollment_schedules');
    }
};