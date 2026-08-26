<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('enrollments', function (Blueprint $table) {
            $table->id();
            $table->string('reference')->unique();      // PTT-2026-00124

            $table->string('name');
            $table->string('phone', 40);
            $table->string('email');

            $table->string('electrical_experience')->nullable();
            $table->string('plc_experience')->nullable();

            $table->foreignId('program_id')->constrained()->restrictOnDelete();
            $table->foreignId('class_session_id')->nullable()->constrained()->nullOnDelete();
            $table->date('preferred_date')->nullable();

            // started → waiver signed → paid (deposit or full). abandoned/cancelled are terminal.
            $table->enum('status', [
                'started', 'waiver_signed', 'deposit_paid', 'paid', 'cancelled', 'abandoned',
            ])->default('started');

            // A seat is only really held until this moment; expired holds free the seat.
            $table->timestamp('seat_hold_expires_at')->nullable();

            $table->unsignedInteger('tuition_cents')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index(['class_session_id', 'status']);
            $table->index('email');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('enrollments');
    }
};
