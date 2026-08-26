<x-mail::message>
# New enrollment

**{{ $enrollment->name }}** just completed checkout.

<x-mail::panel>
**Reference:** {{ $enrollment->reference }}
**Program:** {{ $enrollment->program->name }}
**Session:** {{ optional($enrollment->classSession)->label ?? '—' }}
**Start date:** {{ optional($enrollment->preferred_date)->format('D, M j, Y') ?? '—' }}
</x-mail::panel>

## Student

| | |
|---|---|
| Phone | {{ $enrollment->phone }} |
| Email | {{ $enrollment->email }} |
| Electrical experience | {{ $enrollment->electrical_experience ?? '—' }} |
| PLC experience | {{ $enrollment->plc_experience ?? '—' }} |

## Payment

| | |
|---|---|
| Type | {{ $payment->type === 'full' ? 'Paid in full' : 'Deposit (' . config('ptt.deposit_percent') . '%)' }} |
| Amount | ${{ number_format($payment->amount_cents / 100, 2) }} |
| Card | {{ $payment->card_brand ? ucfirst($payment->card_brand) . ' ••••' . $payment->card_last4 : '—' }} |
| Balance due on site | ${{ number_format($enrollment->balanceDueCents() / 100, 2) }} |

@if ($enrollment->waiver)
Waiver signed {{ $enrollment->waiver->signed_at->format('M j, Y g:i A') }} — emergency contact
{{ $enrollment->waiver->emergency_contact }} ({{ $enrollment->waiver->emergency_phone }}).
@endif

<x-mail::button :url="url('/admin/enrollments/' . $enrollment->id)">
Open in admin
</x-mail::button>
</x-mail::message>
