<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('instructor_invitations')) {
            Schema::create('instructor_invitations', function (Blueprint $t) {
                $t->id();
                $t->string('token', 64)->unique();
                $t->string('email');
                $t->string('name')->nullable();

                // Emailed code, hashed — a leaked database row must not be a login.
                $t->string('otp_hash')->nullable();
                $t->timestamp('otp_expires_at')->nullable();
                $t->unsignedTinyInteger('otp_attempts')->default(0);
                $t->timestamp('otp_sent_at')->nullable();
                $t->boolean('email_verified')->default(false);

                $t->dateTime('expires_at');
                $t->timestamp('consumed_at')->nullable();
                $t->foreignId('instructor_id')->nullable()->constrained()->nullOnDelete();
                $t->foreignId('invited_by')->nullable()->constrained('users')->nullOnDelete();
                $t->timestamps();
            });
        }

        // What the instructor actually signed.
        if (! Schema::hasTable('instructor_agreements')) {
            Schema::create('instructor_agreements', function (Blueprint $t) {
                $t->id();
                $t->foreignId('instructor_id')->constrained()->cascadeOnDelete();
                $t->string('legal_name');
                $t->string('phone');
                $t->string('email');
                $t->string('address');
                $t->string('city');
                $t->string('signature');              // typed legal name
                $t->dateTime('signed_at');
                $t->string('agreement_version')->default('PTT-ICA-2026.1');
                $t->string('ip_address', 45)->nullable();
                $t->text('user_agent')->nullable();
                $t->timestamps();
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_agreements');
        Schema::dropIfExists('instructor_invitations');
    }
};
