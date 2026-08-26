<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('certificates', function (Blueprint $table) {
            $table->id();
            // A certificate can exist for a student enrolled before this system.
            $table->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();

            $table->string('certificate_number')->unique();   // PTT-2026-00124
            $table->string('student_name');
            $table->string('course');
            $table->date('completed_on');
            $table->string('photo_path')->nullable();

            // Only public records are returned by the verification endpoint.
            $table->boolean('is_public')->default(true);
            $table->timestamps();

            $table->index('student_name');
            $table->index(['is_public', 'completed_on']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('certificates');
    }
};
