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
            $table->foreignId('course_id')->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('study_plan', function (Blueprint $table) {
            // Check if we should revert. If it was originally nullable, this might be redundant or wrong.
            // But usually down() reverses the action. 
            // Since the create migration had it nullable, reverting to not null might be wrong if we assume strict reversal to "before state".
            // However, assuming the purpose of this migration is to "ensure" it is nullable, the reverse is "ensure it is NOT nullable".
            // But if the previous state WAS nullable, then this migration was a no-op, and down() makes it distinct.
            // Safest for down() is to make it nullable(false) if that's the "previous" known state, but here the previous state IS nullable.
            // So technically this migration shouldn't exist if the create migration worked. 
            // But I will follow orders.
            $table->foreignId('course_id')->nullable(false)->change();
        });
    }
};
