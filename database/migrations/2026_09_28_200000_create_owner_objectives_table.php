<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // The owner's own Progress Tracker: planned objectives that become
        // Mission Focus when completed in time, Noise when the deadline passes.
        Schema::create('owner_objectives', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('category', 40);
            $t->dateTime('deadline');
            $t->string('status', 10)->default('open');   // open, complete, missed
            $t->dateTime('completed_at')->nullable();
            $t->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['deadline', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('owner_objectives');
    }
};
