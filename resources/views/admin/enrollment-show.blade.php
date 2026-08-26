@extends('admin.layout')
@section('title', $enrollment->reference)

@section('content')
<p><a href="{{ route('admin.enrollments') }}">← All enrollments</a></p>
<h1>{{ $enrollment->name }} <span class="muted" style="font-size:15px">{{ $enrollment->reference }}</span></h1>

<div class="row2">
  <div class="panel" style="padding:18px">
    <strong>Enrollment</strong>
    <dl>
      <dt>Status</dt><dd><x-status-badge :status="$enrollment->status" /></dd>
      <dt>Program</dt><dd>{{ optional($enrollment->program)->name ?? '—' }}</dd>
      <dt>Session</dt><dd>{{ optional($enrollment->classSession)->label ?? '—' }}</dd>
      <dt>Start date</dt><dd>{{ optional($enrollment->preferred_date)->format('l, F j, Y') ?? '—' }}</dd>
      <dt>Phone</dt><dd>{{ $enrollment->phone }}</dd>
      <dt>Email</dt><dd>{{ $enrollment->email }}</dd>
      <dt>Electrical experience</dt><dd>{{ $enrollment->electrical_experience ?? '—' }}</dd>
      <dt>PLC experience</dt><dd>{{ $enrollment->plc_experience ?? '—' }}</dd>
      <dt>Submitted</dt><dd>{{ $enrollment->created_at->format('M j, Y g:i A') }}</dd>
    </dl>

    <form method="POST" action="{{ route('admin.enrollments.update', $enrollment) }}" style="margin-top:18px">
      @csrf @method('PATCH')
      <label style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#8ea0b5">Change status</label>
      <div style="display:flex;gap:10px;margin-top:6px;flex-wrap:wrap">
        <select name="status" style="flex:1 1 180px">
          @foreach (['started','waiver_signed','deposit_paid','paid','cancelled','abandoned'] as $s)
            <option value="{{ $s }}" @selected($enrollment->status === $s)>{{ $s }}</option>
          @endforeach
        </select>
        <button class="btn" type="submit">Save</button>
      </div>
    </form>
  </div>

  <div>
    <div class="panel" style="padding:18px;margin-bottom:18px">
      <strong>Payments</strong>
      <dl>
        <dt>Tuition</dt><dd>${{ number_format($enrollment->tuition_cents / 100, 2) }}</dd>
        <dt>Paid</dt><dd>${{ number_format($enrollment->amountPaidCents() / 100, 2) }}</dd>
        <dt>Balance</dt><dd>${{ number_format($enrollment->balanceDueCents() / 100, 2) }}</dd>
      </dl>
      @if ($enrollment->payments->count())
        <div class="scroll-x" style="margin-top:14px">
        <table>
          <thead><tr><th>Type</th><th>Amount</th><th>Card</th><th>Status</th><th>Paid</th></tr></thead>
          <tbody>
          @foreach ($enrollment->payments as $p)
            <tr>
              <td>{{ ucfirst($p->type) }}</td>
              <td>{{ $p->amount_label }}</td>
              <td class="muted">{{ $p->card_brand ? ucfirst($p->card_brand) . ' ••••' . $p->card_last4 : '—' }}</td>
              <td>{{ $p->status }}</td>
              <td class="muted">{{ optional($p->paid_at)->format('M j, Y g:i A') ?? '—' }}</td>
            </tr>
          @endforeach
          </tbody>
        </table>
        </div>
      @else
        <p class="muted" style="margin:10px 0 0">No payments recorded.</p>
      @endif
    </div>

    <div class="panel" style="padding:18px">
      <strong>Waiver</strong>
      @if ($enrollment->waiver)
        <dl>
          <dt>Legal name</dt><dd>{{ $enrollment->waiver->legal_name }}</dd>
          <dt>Address</dt><dd>{{ $enrollment->waiver->address }}</dd>
          <dt>Emergency contact</dt><dd>{{ $enrollment->waiver->emergency_contact }} — {{ $enrollment->waiver->emergency_phone }}</dd>
          <dt>Photo consent</dt><dd>{{ $enrollment->waiver->photo_consent ? 'Yes' : 'No' }}</dd>
          <dt>Signed</dt><dd>{{ $enrollment->waiver->signed_at->format('M j, Y g:i A') }} <span class="muted">from {{ $enrollment->waiver->ip_address }}</span></dd>
        </dl>
        @if ($enrollment->waiver->signatureUrl())
          <dt style="font-size:11px;letter-spacing:.08em;text-transform:uppercase;color:#8ea0b5;margin-top:14px">Signature</dt>
          <img class="sig" src="{{ $enrollment->waiver->signatureUrl() }}" alt="Student signature">
        @endif
      @else
        <p class="muted" style="margin:10px 0 0">Waiver not signed yet.</p>
      @endif
    </div>
  </div>
</div>
@endsection
