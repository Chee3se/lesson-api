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
        Schema::create('lessons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('day_id')->constrained('days');
            $table->foreignId('week_id')->constrained('weeks');
            $table->integer('period');
            $table->foreignId('division_id')->constrained('divisions');
            $table->foreignId('group_id')->constrained('groups');
            $table->foreignId('subject_id')->constrained('subjects');
            $table->foreignId('classroom_id')->nullable()->constrained('classrooms');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers');
            $table->time('start');
            $table->time('end');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lessons');
    }
};
