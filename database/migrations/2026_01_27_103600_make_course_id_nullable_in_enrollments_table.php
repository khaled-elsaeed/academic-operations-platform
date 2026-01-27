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
        Schema::table('enrollments', function (Blueprint $table) {
            $table->foreignId('course_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('enrollments', function (Blueprint $table) {
            // We cannot easily revert to non-null if there are null values, 
            // but for schema correctness we define the reverse operation.
            // In a real scenario, we might want to delete rows with null course_id or extensive data migration.
            // For now, we attempt to make it non-nullable again.
            $table->foreignId('course_id')->nullable(false)->change();
        });
    }
};
