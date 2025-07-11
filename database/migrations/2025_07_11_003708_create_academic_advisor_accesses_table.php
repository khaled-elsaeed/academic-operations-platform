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
        Schema::create('academic_advisor_accesses', function (Blueprint $table) {
            // Primary Key
            $table->id();

            $table->foreignId('advisor_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->cascadeOnUpdate()
                ->comment('References the advisor from the users table');

            $table->foreignId('level_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->foreignId('program_id')
                ->nullable()
                ->constrained()
                ->cascadeOnDelete()
                ->cascadeOnUpdate();

            $table->boolean('is_active')
                ->default(true)
                ->comment('Indicates if the access rule is active');

            $table->timestamps();

            // Unique constraint
            $table->unique(
                ['advisor_id', 'level_id', 'program_id'],
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('academic_advisor_access');
    }
};
