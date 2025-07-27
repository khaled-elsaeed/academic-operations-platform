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
         Schema::create('available_course_details', function (Blueprint $table) {
            $table->id();
            $table->foreignId('available_course_id')->constrained('available_courses')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('group')->default(1);
            $table->enum('activity_type', ['lecture', 'lab', 'tutorial'])->default('lecture');
            $table->unsignedInteger('min_capacity')->default(1);
            $table->unsignedInteger('max_capacity')->default(30);
            $table->unsignedInteger('capacity')->nullable();
            $table->timestamps();

            $table->unique(['available_course_id', 'group', 'activity_type'], 'unique_available_course_detail');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('available_course_details');
    }
};
