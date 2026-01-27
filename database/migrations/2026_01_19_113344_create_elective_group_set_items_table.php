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
        Schema::create('elective_group_set_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('elective_group_set_id')->constrained()->cascadeOnDelete();
            $table->foreignId('elective_group_id')->constrained('elective_courses')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('elective_group_set_items');
    }
};
