@extends('admin.layout')
@section('title', 'Dashboard')

@section('content')
<h1>Dashboard</h1>

<div class="cards">
  <div class="card"><div class="k">Confirmed enrollments</div><div class="v">{{ $totalEnrollments }}</div></div>
  <div class="card"><div class="k">In progress</div><div class="v">{{ $pending }}</div></div>
  <div class="card"><div class="k">Collected</div><div class="v">${{ number_format($revenueCents / 100, 2) }}</div></div>
  <div class="card"><div class="k">Certificates</div><div class="v">{{ $certificates }}</div></div>
</div>

<h1 style="font-size:16px">Recent activity</h1>
<div class="panel">
  <div class="scroll-x">
  <table>
    <thead><tr><th>Reference</th><th>Student</th><th>Program</th><th>Start</th><th>Status</th><th>Created</th></tr></thead>
    <tbody>
    @forelse ($recent as $e)
      <tr>
        <td><a href="{{ route('admin.enrollments.show', $e) }}">{{ $e->reference }}</a></td>
        <td>{{ $e->name }}<div class="muted">{{ $e->email }}</div></td>
        <td>{{ optional($e->program)->name ?? '—' }}</td>
        <td>{{ optional($e->preferred_date)->format('M j, Y') ?? '—' }}</td>
        <td><x-status-badge :status="$e->status" /></td>
        <td class="muted">{{ $e->created_at->diffForHumans() }}</td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">No enrollments yet.</td></tr>
    @endforelse
    </tbody>
  </table>
  </div>
</div>
@endsection
