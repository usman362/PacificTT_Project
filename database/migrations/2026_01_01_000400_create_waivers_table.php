<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('waivers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->unique()->constrained()->cascadeOnDelete();

            $table->string('legal_name');
            $table->string('phone', 40);
            $table->string('address');
            $table->string('emergency_contact');
            $table->string('emergency_phone', 40);

            $table->boolean('agreed')->default(false);
            $table->boolean('photo_consent')->default(false);

            // Signature is written to storage; we keep the path, not the blob.
            $table->string('signature_path');
            $table->dateTime('signed_at');

            // Kept for evidentiary value if a waiver is ever disputed.
            $table->ipAddress('ip_address')->nullable();
            $table->string('user_agent', 512)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('waivers');
    }
};
