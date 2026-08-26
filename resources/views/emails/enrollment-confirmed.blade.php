<x-mail::message>
# You're enrolled, {{ $enrollment->name }}

Your seat at **Pacific Trade Tech** is confirmed. Keep this reference for your records.

<x-mail::panel>
**Reference:** {{ $enrollment->reference }}
**Program:** {{ $enrollment->program->name }}
**Session:** {{ optional($enrollment->classSession)->label ?? '—' }}
**Start date:** {{ optional($enrollment->preferred_date)->format('l, F j, Y') ?? '—' }}
</x-mail::panel>

## Payment

| | |
|---|---|
| Tuition | ${{ number_format($enrollment->tuition_cents / 100, 2) }} |
| Paid {{ $payment->paid_at?->format('M j, Y') }} | ${{ number_format($payment->amount_cents / 100, 2) }} ({{ $payment->type === 'full' ? 'paid in full' : 'deposit' }}) |
@if ($enrollment->balanceDueCents() > 0)
| **Balance due on site** | **${{ number_format($enrollment->balanceDueCents() / 100, 2) }}** |
@endif

@if ($enrollment->balanceDueCents() > 0)
The remaining balance is due on your first day of class, before training begins.
@endif

## What to bring

- Photo ID
- Closed-toe safety footwear
- Your reference number: **{{ $enrollment->reference }}**

Questions? Just reply to this email.

Thanks,<br>
Pacific Trade Tech
</x-mail::message>
