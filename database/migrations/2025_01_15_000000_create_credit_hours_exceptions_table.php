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
        Schema::create('credit_hours_exceptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('term_id')->constrained()->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignId('granted_by')->constrained('users')->cascadeOnDelete()->cascadeOnUpdate();
            $table->integer('additional_hours')->default(0); 
            $table->text('reason')->nullable(); 
            $table->boolean('is_active')->default(true); 
            $table->timestamps();

            // Ensure one active exception per student per term
            $table->unique(['student_id', 'term_id', 'is_active'], 'unique_active_exception');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_hours_exceptions');
    }
}; 