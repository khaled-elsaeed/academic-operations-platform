<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {



    public function up(): void
    {
        Schema::create('study_plan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained('programs')->onDelete('cascade');
            $table->integer('semester_no');
            $table->foreignId('course_id')->constrained('courses')->onDelete('cascade')->nullable();
            $table->foreignId('elective_course_id')->nullable()->constrained('elective_courses')->onDelete('cascade');
            $table->string('type')->default('normal');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('study_plan');
    }
};
