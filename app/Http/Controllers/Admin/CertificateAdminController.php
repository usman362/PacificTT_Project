<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Certificate;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class CertificateAdminController extends Controller
{
    public function index(Request $request): View
    {
        $certificates = Certificate::query()
            ->when($request->filled('q'), function ($q) use ($request) {
                $term = '%' . $request->string('q')->trim() . '%';
                $q->where('student_name', 'like', $term)
                  ->orWhere('certificate_number', 'like', $term);
            })
            ->latest('completed_on')
            ->paginate(25)
            ->withQueryString();

        return view('admin.certificates', compact('certificates'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'certificate_number' => ['required', 'string', 'max:60', 'unique:certificates,certificate_number'],
            'student_name'       => ['required', 'string', 'max:150'],
            'course'             => ['required', 'string', 'max:150'],
            'completed_on'       => ['required', 'date'],
            'is_public'          => ['nullable', 'boolean'],
            'photo'              => ['nullable', 'image', 'max:2048'],
        ]);

        if ($request->hasFile('photo')) {
            $data['photo_path'] = $request->file('photo')->store('certificates', 'public');
        }
        unset($data['photo']);

        $data['certificate_number'] = strtoupper($data['certificate_number']);
        $data['is_public'] = $request->boolean('is_public');

        Certificate::create($data);

        return back()->with('status', 'Certificate added to the public registry.');
    }

    public function destroy(Certificate $certificate): RedirectResponse
    {
        if ($certificate->photo_path) {
            Storage::disk('public')->delete($certificate->photo_path);
        }

        $certificate->delete();

        return back()->with('status', 'Certificate removed.');
    }
}
