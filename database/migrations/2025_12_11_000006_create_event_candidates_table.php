<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('event_candidates', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('candidate_id')->constrained()->cascadeOnDelete();
            $table->timestamp('registered_at')->useCurrent();
            $table->timestamps();

            $table->unique(['event_id', 'candidate_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('event_candidates');
    }
};
