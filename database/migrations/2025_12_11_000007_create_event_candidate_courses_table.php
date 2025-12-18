<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_candidate_courses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->foreignId('course_id')->constrained()->cascadeOnDelete();
            $table->enum('attended', ['yes', 'no', 'partial'])->nullable();
            $table->enum('result', ['pass', 'fail', 'pending'])->default('pending');
            $table->text('notes')->nullable();
            $table->timestamp('marked_at')->nullable();
            $table->timestamps();

            $table->unique(['event_id', 'candidate_id', 'course_id'], 'unique_attendance');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_candidate_courses');
    }
};
