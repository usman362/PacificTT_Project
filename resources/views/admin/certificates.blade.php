@extends('admin.layout')
@section('title', 'Certificates')

@section('content')
<h1>Certificate registry</h1>
<p class="muted" style="margin-top:-10px">Records marked public are returned by the graduate verification search on the website.</p>

<div class="panel" style="padding:18px;margin-bottom:22px">
  <strong>Add a certificate</strong>
  <form method="POST" action="{{ route('admin.certificates.store') }}" enctype="multipart/form-data" style="margin-top:12px">
    @csrf
    <div class="filters">
      <div>
        <label class="muted" style="font-size:11px">Certificate number</label>
        <input name="certificate_number" value="{{ old('certificate_number') }}" placeholder="PTT-2026-00125" required>
        @error('certificate_number')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="muted" style="font-size:11px">Student name</label>
        <input name="student_name" value="{{ old('student_name') }}" required>
        @error('student_name')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="muted" style="font-size:11px">Course</label>
        <input name="course" value="{{ old('course') }}" required>
        @error('course')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="muted" style="font-size:11px">Completed on</label>
        <input name="completed_on" type="date" value="{{ old('completed_on') }}" required>
        @error('completed_on')<div class="err">{{ $message }}</div>@enderror
      </div>
      <div>
        <label class="muted" style="font-size:11px">Photo (optional)</label>
        <input name="photo" type="file" accept="image/*">
        @error('photo')<div class="err">{{ $message }}</div>@enderror
      </div>
    </div>
    <label style="display:flex;align-items:center;gap:8px;margin:6px 0 14px;color:#b9c6d4">
      <input type="checkbox" name="is_public" value="1" checked style="width:18px;height:18px;min-height:0"> Publicly verifiable
    </label>
    <button class="btn" type="submit">Add certificate</button>
  </form>
</div>

<form method="GET" style="display:flex;gap:10px;margin-bottom:14px;flex-wrap:wrap">
  <input type="search" name="q" value="{{ request('q') }}" placeholder="Search name or number" style="flex:1 1 240px">
  <button class="btn" type="submit">Search</button>
</form>

<div class="panel">
  <div class="scroll-x">
  <table>
    <thead><tr><th>Number</th><th>Student</th><th>Course</th><th>Completed</th><th>Public</th><th></th></tr></thead>
    <tbody>
    @forelse ($certificates as $c)
      <tr>
        <td>{{ $c->certificate_number }}</td>
        <td>{{ $c->student_name }}</td>
        <td>{{ $c->course }}</td>
        <td>{{ $c->completed_on->format('M j, Y') }}</td>
        <td>{{ $c->is_public ? 'Yes' : 'No' }}</td>
        <td>
          <form method="POST" action="{{ route('admin.certificates.destroy', $c) }}"
                onsubmit="return confirm('Remove {{ $c->certificate_number }} from the registry?')">
            @csrf @method('DELETE')
            <button class="btn btn--ghost" type="submit">Remove</button>
          </form>
        </td>
      </tr>
    @empty
      <tr><td colspan="6" class="muted">No certificates yet.</td></tr>
    @endforelse
    </tbody>
  </table>
  </div>
  <div class="pager">{{ $certificates->links() }}</div>
</div>
@endsection
