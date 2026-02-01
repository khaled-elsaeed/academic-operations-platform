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
        // Fix NULLs
        DB::table('available_course_schedules')
            ->whereNull('current_capacity')
            ->update(['current_capacity' => 0]);

        // Fix negatives
        DB::table('available_course_schedules')
            ->where('current_capacity', '<', 0)
            ->update(['current_capacity' => 0]);

        // Fix non-numeric values (MySQL)
        DB::statement("
            UPDATE available_course_schedules
            SET current_capacity = 0
            WHERE current_capacity REGEXP '[^0-9]'
        ");

        Schema::table('available_course_schedules', function (Blueprint $table) {
            $table->unsignedInteger('current_capacity')
                ->default(0)
                ->nullable(false)
                ->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_course_schedules', function (Blueprint $table) {
            $table->unsignedInteger('current_capacity')->nullable()->change();
        });
    }
};
