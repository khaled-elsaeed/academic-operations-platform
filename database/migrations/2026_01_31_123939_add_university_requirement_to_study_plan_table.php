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
        Schema::table('study_plan', function (Blueprint $table) {
            $table->foreignId('university_requirement_id')
                ->nullable()
                ->after('elective_course_id')
                ->constrained('university_requirements')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_plan', function (Blueprint $table) {
            $table->dropForeign(['university_requirement_id']);
            $table->dropColumn('university_requirement_id');
        });
    }
};
