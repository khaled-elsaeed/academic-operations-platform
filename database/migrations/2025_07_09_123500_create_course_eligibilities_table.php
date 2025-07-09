<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('course_eligibilities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('available_course_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('level_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            $table->unique(['available_course_id', 'program_id', 'level_id'], 'course_eligibility_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('course_eligibilities');
    }
}; 