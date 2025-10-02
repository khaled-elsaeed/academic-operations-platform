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
        Schema::table('available_course_schedules', function (Blueprint $table) {
            $table->dropForeign(['available_course_id']);
            $table->dropUnique('unique_available_course_detail');
            $table->unique(['available_course_id', 'group', 'activity_type', 'location'], 'unique_available_course_detail');
            $table->foreign('available_course_id')->references('id')->on('available_courses')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_course_schedules', function (Blueprint $table) {
            $table->dropForeign(['available_course_id']);
            $table->dropUnique('unique_available_course_detail');
            $table->unique(['available_course_id', 'group', 'activity_type'], 'unique_available_course_detail');
            $table->foreign('available_course_id')->references('id')->on('available_courses')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }
};
