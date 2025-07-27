<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('schedule_slots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained()->cascadeOnDelete();
            $table->string('slot_identifier');
            $table->time('start_time');
            $table->time('end_time');
            $table->integer('duration_minutes');
            $table->date('specific_date')->nullable();
            $table->enum('day_of_week', ['monday', 'tuesday', 'wednesday', 'thursday', 'friday', 'saturday', 'sunday'])->nullable(); // For repetitive schedules
            $table->integer('slot_order')->default(1); 
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            
            // Unique constraint for repetitive schedules
            $table->unique(['schedule_id', 'day_of_week', 'slot_order'], 'unique_repetitive_slot');
            // Unique constraint for specific date schedules
            $table->unique(['schedule_id', 'specific_date', 'start_time'], 'unique_specific_slot');
        });

        Schema::create('schedule_assignments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_slot_id')->constrained()->cascadeOnDelete();
            $table->morphs('assignable');
            $table->string('title'); 
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->integer('capacity')->nullable();
            $table->integer('enrolled')->default(0);
            $table->json('resources')->nullable(); 
            $table->enum('status', ['scheduled', 'confirmed', 'cancelled', 'completed'])->default('scheduled');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('schedule_assignments');
        Schema::dropIfExists('schedule_slots');
    }
};