<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('enrollment_id')->constrained()->cascadeOnDelete();

            $table->enum('type', ['deposit', 'full']);
            $table->unsignedInteger('amount_cents');
            $table->char('currency', 3)->default('usd');

            // No card data is ever stored here — only Stripe's identifiers.
            $table->string('stripe_payment_intent_id')->nullable()->unique();
            $table->string('card_brand', 40)->nullable();
            $table->string('card_last4', 4)->nullable();

            $table->enum('status', ['pending', 'succeeded', 'failed', 'refunded'])->default('pending');
            $table->string('failure_message', 512)->nullable();
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
