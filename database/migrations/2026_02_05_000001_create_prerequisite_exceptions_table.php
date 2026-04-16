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
        Schema::create('prerequisite_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('prerequisite_id')->constrained('courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->text('reason')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // Ensure one exception per student per course per prerequisite per term
            $table->unique(
                ['student_id', 'course_id', 'prerequisite_id', 'term_id'],
                'unique_student_course_prereq_term_exception'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('prerequisite_exceptions');
    }
};
