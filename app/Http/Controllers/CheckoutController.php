<?php

namespace App\Http\Controllers;

use App\Mail\EnrollmentConfirmed;
use App\Mail\AdminEnrollmentAlert;
use App\Models\Enrollment;
use App\Models\Payment;
use App\Services\StripeGateway;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\Rule;

class CheckoutController extends Controller
{
    public function __construct(private readonly StripeGateway $stripe)
    {
    }

    /** Summary shown on the checkout screen. */
    public function summary(Enrollment $enrollment): JsonResponse
    {
        $enrollment->load('program');
        $tuition = (int) $enrollment->tuition_cents;
        $deposit = $this->stripe->amountFor($enrollment, 'deposit');

        return response()->json([
            'reference'      => $enrollment->reference,
            'program'        => $enrollment->program->name,
            'session_slot'   => optional($enrollment->classSession)->label,
            'preferred_date' => optional($enrollment->preferred_date)->toDateString(),
            'tuition_cents'  => $tuition,
            'deposit_cents'  => $deposit,
            'balance_cents'  => $tuition - $deposit,
            'expired'        => $enrollment->isSeatHoldExpired(),
            'hold_expires'   => optional($enrollment->seat_hold_expires_at)->toIso8601String(),
        ]);
    }

    /** Creates the PaymentIntent the browser will confirm with Stripe.js. */
    public function intent(Request $request, Enrollment $enrollment): JsonResponse
    {
        $data = $request->validate([
            'type' => ['required', Rule::in(['deposit', 'full'])],
        ]);

        if ($enrollment->isSeatHoldExpired()) {
            return response()->json([
                'ok' => false, 'expired' => true,
                'message' => 'Your seat hold expired. Please start again.',
            ], 409);
        }

        if ($enrollment->status === 'started') {
            return response()->json([
                'ok' => false,
                'message' => 'The waiver must be signed before payment.',
            ], 422);
        }

        if (! $this->stripe->configured()) {
            return response()->json([
                'ok' => false,
                'message' => 'Payments are not configured yet. Please contact the office to complete enrollment.',
            ], 503);
        }

        try {
            $intent = $this->stripe->intentFor($enrollment, $data['type']);
        } catch (\Throwable $e) {
            Log::error('Stripe intent failed', ['ref' => $enrollment->reference, 'error' => $e->getMessage()]);

            return response()->json([
                'ok' => false,
                'message' => 'We could not start the payment. Please try again in a moment.',
            ], 502);
        }

        return response()->json(['ok' => true] + $intent);
    }

    /**
     * Called by the browser after Stripe confirms. This is a convenience for UX
     * only — the webhook is the authority on whether money actually moved.
     */
    public function confirm(Request $request, Enrollment $enrollment): JsonResponse
    {
        $data = $request->validate([
            'payment_intent_id' => ['required', 'string', 'max:120'],
        ]);

        if (! $this->stripe->configured()) {
            return response()->json(['ok' => false], 503);
        }

        try {
            $intent = $this->stripe->client()->paymentIntents->retrieve($data['payment_intent_id']);
        } catch (\Throwable $e) {
            Log::error('Stripe confirm lookup failed', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false, 'message' => 'Could not verify the payment.'], 502);
        }

        if ($intent->status !== 'succeeded') {
            return response()->json([
                'ok'      => false,
                'status'  => $intent->status,
                'message' => 'Payment has not completed yet.',
            ], 202);
        }

        $this->markPaid($intent);

        return response()->json(['ok' => true, 'reference' => $enrollment->fresh()->reference]);
    }

    /** Stripe → us. Signature-verified; the source of truth for payment state. */
    public function webhook(Request $request): JsonResponse
    {
        $secret = config('services.stripe.webhook_secret');

        if (blank($secret)) {
            Log::warning('Stripe webhook hit but no signing secret is configured.');
            return response()->json(['ok' => false], 503);
        }

        try {
            $event = \Stripe\Webhook::constructEvent(
                $request->getContent(),
                $request->header('Stripe-Signature', ''),
                $secret
            );
        } catch (\Throwable $e) {
            Log::warning('Stripe webhook signature rejected', ['error' => $e->getMessage()]);
            return response()->json(['ok' => false], 400);
        }

        if ($event->type === 'payment_intent.succeeded') {
            $this->markPaid($event->data->object);
        }

        if ($event->type === 'payment_intent.payment_failed') {
            $intent = $event->data->object;
            Payment::where('stripe_payment_intent_id', $intent->id)->update([
                'status'          => 'failed',
                'failure_message' => substr((string) ($intent->last_payment_error->message ?? 'Payment failed'), 0, 500),
            ]);
        }

        return response()->json(['ok' => true]);
    }

    /**
     * Idempotent: safe to run from both the browser confirm and the webhook,
     * in either order, without double-sending mail or double-counting money.
     */
    private function markPaid(object $intent): void
    {
        DB::transaction(function () use ($intent) {
            $payment = Payment::where('stripe_payment_intent_id', $intent->id)->lockForUpdate()->first();

            if (! $payment || $payment->status === 'succeeded') {
                return; // unknown intent, or already handled
            }

            $card = $intent->charges->data[0]->payment_method_details->card ?? null;

            $payment->update([
                'status'     => 'succeeded',
                'paid_at'    => now(),
                'card_brand' => $card->brand ?? null,
                'card_last4' => $card->last4 ?? null,
            ]);

            $enrollment = $payment->enrollment()->lockForUpdate()->first();

            $enrollment->update([
                'status'               => $payment->type === 'full' ? 'paid' : 'deposit_paid',
                // Payment secures the seat permanently — the hold no longer applies.
                'seat_hold_expires_at' => null,
            ]);

            // Send after commit so a rolled-back transaction never mails a student.
            DB::afterCommit(function () use ($enrollment, $payment) {
                try {
                    Mail::to($enrollment->email)->send(new EnrollmentConfirmed($enrollment, $payment));
                    Mail::to(config('ptt.admin_email'))->send(new AdminEnrollmentAlert($enrollment, $payment));
                } catch (\Throwable $e) {
                    Log::error('Enrollment mail failed', [
                        'ref' => $enrollment->reference, 'error' => $e->getMessage(),
                    ]);
                }
            });
        });
    }
}
