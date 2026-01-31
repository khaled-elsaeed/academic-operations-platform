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
        // 1. Main table for University Requirements (e.g., UE1, ElectiveX)
        Schema::create('university_requirements', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('code')->unique(); // e.g., 'UE1', 'ArtElective'
            $table->enum('type', ['elective', 'compulsory']); 
            
            $table->foreignId('course_id')->nullable()->constrained('courses')->nullOnDelete(); 

            $table->timestamps();
        });

        // 2. Group Sets (Pools)
        Schema::create('university_requirement_group_sets', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->timestamps();
        });

        // 3. Link Requirements to Group Sets
        Schema::create('university_requirement_group_set_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_requirement_id');
            $table->unsignedBigInteger('university_requirement_group_set_id');
            $table->timestamps();

            // Manual FKs with short names
            $table->foreign('university_requirement_id', 'fk_ur_items_req_id')
                  ->references('id')->on('university_requirements')
                  ->cascadeOnDelete();
            
            $table->foreign('university_requirement_group_set_id', 'fk_ur_items_set_id')
                  ->references('id')->on('university_requirement_group_sets')
                  ->cascadeOnDelete();
        });

        // 4. Define courses inside the Group Sets
        Schema::create('university_requirement_courses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('university_requirement_group_set_id');
            $table->foreignId('course_id')->constrained('courses')->cascadeOnDelete();
            $table->timestamps();

            $table->foreign('university_requirement_group_set_id', 'fk_ur_courses_set_id')
                  ->references('id')->on('university_requirement_group_sets')
                  ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('university_requirement_courses');
        Schema::dropIfExists('university_requirement_group_set_items');
        Schema::dropIfExists('university_requirement_group_sets');
        Schema::dropIfExists('university_requirements');
    }
};
