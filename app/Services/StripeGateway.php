<?php

namespace App\Services;

use App\Models\Enrollment;
use App\Models\Payment;
use Stripe\StripeClient;

/**
 * Thin wrapper around Stripe.
 *
 * Card details are collected by Stripe Elements in the browser and exchanged
 * for a PaymentIntent client secret — no card number, expiry or CVC ever
 * reaches this application or its database.
 */
class StripeGateway
{
    public function configured(): bool
    {
        return filled(config('services.stripe.secret')) && filled(config('services.stripe.key'));
    }

    public function client(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    /** Amount owed right now for the chosen payment type, in cents. */
    public function amountFor(Enrollment $enrollment, string $type): int
    {
        $tuition = (int) $enrollment->tuition_cents;

        return $type === 'deposit'
            ? (int) round($tuition * config('ptt.deposit_percent') / 100)
            : $tuition;
    }

    /**
     * Creates (or reuses) a PaymentIntent for this enrolment and returns the
     * client secret the browser needs.
     */
    public function intentFor(Enrollment $enrollment, string $type): array
    {
        $amount = $this->amountFor($enrollment, $type);

        $payment = Payment::firstOrNew([
            'enrollment_id' => $enrollment->id,
            'type'          => $type,
            'status'        => 'pending',
        ]);

        $stripe = $this->client();

        if ($payment->stripe_payment_intent_id) {
            $intent = $stripe->paymentIntents->retrieve($payment->stripe_payment_intent_id);

            // If the amount changed (e.g. program switched), update the intent.
            if ($intent->amount !== $amount && $intent->status === 'requires_payment_method') {
                $intent = $stripe->paymentIntents->update($intent->id, ['amount' => $amount]);
            }
        } else {
            $intent = $stripe->paymentIntents->create([
                'amount'   => $amount,
                'currency' => 'usd',
                'metadata' => [
                    'enrollment_id'  => (string) $enrollment->id,
                    'reference'      => $enrollment->reference,
                    'payment_type'   => $type,
                ],
                'description'          => "PTT {$enrollment->reference} — " . ucfirst($type) . ' tuition',
                'receipt_email'        => $enrollment->email,
                'automatic_payment_methods' => ['enabled' => true],
            ]);
        }

        $payment->fill([
            'amount_cents'             => $amount,
            'currency'                 => 'usd',
            'stripe_payment_intent_id' => $intent->id,
        ])->save();

        return [
            'client_secret' => $intent->client_secret,
            'amount_cents'  => $amount,
            'payment_id'    => $payment->id,
        ];
    }
}
