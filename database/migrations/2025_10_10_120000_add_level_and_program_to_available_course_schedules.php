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
            // Some MySQL setups require dropping dependent foreign keys first
            try {
                $table->dropForeign(['available_course_id']);
            } catch (\Exception $e) {
                // ignore if not exists
            }

            // Drop the existing unique constraint so the schedule can be differentiated by level/program later
            try {
                $table->dropUnique('unique_available_course_detail');
            } catch (\Exception $e) {
                // ignore if it doesn't exist
            }

            // Add nullable foreign keys
            $table->foreignId('level_id')->nullable()->constrained('levels')->nullOnDelete()->cascadeOnUpdate();
            $table->foreignId('program_id')->nullable()->constrained('programs')->nullOnDelete()->cascadeOnUpdate();

            // Recreate foreign key to available_courses
            $table->foreign('available_course_id')->references('id')->on('available_courses')->cascadeOnDelete()->cascadeOnUpdate();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_course_schedules', function (Blueprint $table) {
            // Drop the FKs and columns
            if (Schema::hasColumn('available_course_schedules', 'level_id')) {
                $table->dropForeign(['level_id']);
                $table->dropColumn('level_id');
            }
            if (Schema::hasColumn('available_course_schedules', 'program_id')) {
                $table->dropForeign(['program_id']);
                $table->dropColumn('program_id');
            }

            // Recreate the previous unique constraint (includes location as per last migration)
            try {
                $table->dropForeign(['available_course_id']);
            } catch (\Exception $e) {
                // ignore
            }

            try {
                $table->unique(['available_course_id', 'group', 'activity_type', 'location'], 'unique_available_course_detail');
            } catch (\Exception $e) {
                // ignore if it already exists or fails
            }

            // Recreate foreign key to available_courses
            try {
                $table->foreign('available_course_id')->references('id')->on('available_courses')->cascadeOnDelete()->cascadeOnUpdate();
            } catch (\Exception $e) {
                // ignore
            }
        });
    }
};
