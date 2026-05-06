<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('training_requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('schedule_id')->constrained('team_schedules')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('coach_id')->constrained('coaches')->cascadeOnDelete();
            $table->string('title');
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['schedule_id', 'student_id']);
            $table->index(['coach_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('training_requirements');
    }
};
