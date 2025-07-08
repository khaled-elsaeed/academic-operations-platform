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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            $table->string('name_en')->unique();
            $table->string('name_ar')->nullable()->unique();
            $table->string('academic_id')->unique();
            $table->string('national_id')->unique();
            $table->string('academic_email')->unique();
            $table->string('level');
            $table->decimal('cgpa', 4, 3);
            $table->enum('gender', ['male', 'female']);
            $table->foreignId('program_id')->constrained()->restrictOnDelete()->cascadeOnUpdate();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};
