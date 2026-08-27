<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class Waiver extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id', 'legal_name', 'phone', 'address',
        'emergency_contact', 'emergency_phone',
        'agreed', 'photo_consent', 'signature_path', 'signed_at', 'needs_review', 'staff_user_id', 'staff_accepted_on', 'staff_signature', 'staff_name',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'agreed'        => 'boolean',
            'photo_consent' => 'boolean',
            'signed_at'     => 'datetime',
            'staff_accepted_on' => 'date',
            'needs_review'      => 'boolean',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function signatureUrl(): ?string
    {
        return $this->signature_path ? Storage::url($this->signature_path) : null;
    }
}
