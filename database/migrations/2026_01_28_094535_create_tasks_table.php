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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();  // Unique identifier for the task
            $table->foreignId('user_id')->constrained()->onDelete('cascade');  // Link to the user who initiated the task
            $table->string('type');
            $table->string('subtype')->nullable();
            $table->string('status')->default('pending');  // pending, processing, completed, failed
            $table->string('message')->nullable();
            $table->integer('progress')->default(0);  // 0-100 percentage
            $table->json('parameters')->nullable();  // JSON for params, e.g., {'file': 'data.csv', 'filters': {...}}
            $table->json('result')->nullable();      // JSON for result, e.g., {'file_path': '/storage/exports/data.xlsx', 'message': 'Success'}
            $table->json('errors')->nullable();      // JSON for error messages array

            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};
