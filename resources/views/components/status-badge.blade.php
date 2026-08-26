@props(['status'])
@php
$map = [
  'paid'          => ['b-paid', 'Paid'],
  'deposit_paid'  => ['b-deposit', 'Deposit'],
  'waiver_signed' => ['b-waiver', 'Waiver signed'],
  'started'       => ['b-started', 'Started'],
  'cancelled'     => ['b-cancelled', 'Cancelled'],
  'abandoned'     => ['b-abandoned', 'Abandoned'],
];
[$class, $label] = $map[$status] ?? ['b-started', $status];
@endphp
<span class="badge {{ $class }}">{{ $label }}</span>
