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
        Schema::create('student_schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('available_course_schedule_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->enum('status', ['active', 'dropped', 'completed'])->default('active');
            $table->timestamps();

            // Unique constraint to prevent duplicate assignments
            $table->unique(['student_id', 'available_course_schedule_id', 'term_id'], 'unique_student_schedule');
            
            // Index for better performance
            $table->index(['student_id', 'term_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_schedule_assignments');
    }
};