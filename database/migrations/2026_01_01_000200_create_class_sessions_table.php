<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('program_id')->constrained()->cascadeOnDelete();
            // "Morning (8am-12pm)" etc.
            $table->string('label');
            $table->date('start_date');
            $table->unsignedSmallInteger('capacity')->default(12);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            // One row per program + slot + day; seat counts are derived from
            // enrollments, never stored, so they cannot drift.
            $table->unique(['program_id', 'label', 'start_date'], 'class_sessions_unique_slot');
            $table->index(['start_date', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_sessions');
    }
};
