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
        Schema::table('available_courses', function (Blueprint $table) {
            
            $table->enum('mode', ['individual', 'all_programs', 'all_levels', 'universal'])
                  ->default('individual')
                  ->after('term_id');
            
            if (Schema::hasColumn('available_courses', 'is_universal')) {
                $table->dropColumn('is_universal');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('available_courses', function (Blueprint $table) {
            $table->boolean('is_universal')->default(false)->after('term_id');
            $table->dropColumn('mode');
        });
    }
};