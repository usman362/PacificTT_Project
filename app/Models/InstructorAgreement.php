<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InstructorAgreement extends Model
{
    protected $fillable = [
        'instructor_id', 'legal_name', 'phone', 'email', 'address', 'city',
        'signature', 'signed_at', 'agreement_version', 'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return ['signed_at' => 'datetime'];
    }

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }
}
