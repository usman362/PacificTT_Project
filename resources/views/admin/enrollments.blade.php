@extends('admin.layout')
@section('title', 'Enrollments')

@section('content')
<h1>Enrollments</h1>

<form method="GET" action="{{ route('admin.enrollments') }}">
  <div class="filters">
    <input type="search" name="q" value="{{ $filters['q'] ?? '' }}" placeholder="Name, email, phone or reference">
    <select name="status">
      <option value="">All statuses</option>
      @foreach (['started'=>'Started','waiver_signed'=>'Waiver signed','deposit_paid'=>'Deposit paid','paid'=>'Paid','cancelled'=>'Cancelled','abandoned'=>'Abandoned'] as $v => $l)
        <option value="{{ $v }}" @selected(($filters['status'] ?? '') === $v)>{{ $l }}</option>
      @endforeach
    </select>
    <select name="program_id">
      <option value="">All programs</option>
      @foreach ($programs as $p)
        <option value="{{ $p->id }}" @selected((string)($filters['program_id'] ?? '') === (string)$p->id)>{{ $p->name }}</option>
      @endforeach
    </select>
    <input type="date" name="from" value="{{ $filters['from'] ?? '' }}" aria-label="From date">
    <input type="date" name="to" value="{{ $filters['to'] ?? '' }}" aria-label="To date">
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;margin-bottom:16px">
    <button class="btn" type="submit">Filter</button>
    <a class="btn btn--ghost" href="{{ route('admin.enrollments') }}">Reset</a>
    <a class="btn btn--ghost" href="{{ route('admin.enrollments.export', request()->query()) }}">Export CSV</a>
  </div>
</form>

<div class="panel">
  <div class="scroll-x">
  <table>
    <thead>
      <tr><th>Reference</th><th>Student</th><th>Program</th><th>Session</th><th>Start</th><th>Paid</th><th>Status</th></tr>
    </thead>
    <tbody>
    @forelse ($enrollments as $e)
      <tr>
        <td><a href="{{ route('admin.enrollments.show', $e) }}">{{ $e->reference }}</a></td>
        <td>{{ $e->name }}<div class="muted">{{ $e->email }}<br>{{ $e->phone }}</div></td>
        <td>{{ optional($e->program)->name ?? '—' }}</td>
        <td class="muted">{{ optional($e->classSession)->label ?? '—' }}</td>
        <td>{{ optional($e->preferred_date)->format('M j, Y') ?? '—' }}</td>
        <td>${{ number_format($e->amountPaidCents() / 100, 2) }}
          @if ($e->balanceDueCents() > 0)
            <div class="muted">${{ number_format($e->balanceDueCents() / 100, 2) }} due</div>
          @endif
        </td>
        <td><x-status-badge :status="$e->status" /></td>
      </tr>
    @empty
      <tr><td colspan="7" class="muted">No enrollments match those filters.</td></tr>
    @endforelse
    </tbody>
  </table>
  </div>
  <div class="pager">{{ $enrollments->links() }}</div>
</div>
@endsection
