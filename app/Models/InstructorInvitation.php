<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class InstructorInvitation extends Model
{
    protected $fillable = [
        'token', 'email', 'name', 'otp_hash', 'otp_expires_at', 'otp_attempts',
        'otp_sent_at', 'email_verified', 'expires_at', 'consumed_at',
        'instructor_id', 'invited_by',
    ];

    protected $hidden = ['otp_hash'];

    protected function casts(): array
    {
        return [
            'otp_expires_at' => 'datetime',
            'otp_sent_at'    => 'datetime',
            'expires_at'     => 'datetime',
            'consumed_at'    => 'datetime',
            'email_verified' => 'boolean',
        ];
    }

    public const MAX_ATTEMPTS = 5;

    public function instructor(): BelongsTo
    {
        return $this->belongsTo(Instructor::class);
    }

    public static function issue(string $email, ?string $name, ?int $invitedBy = null): self
    {
        return static::create([
            'token'      => Str::random(48),
            'email'      => $email,
            'name'       => $name,
            'expires_at' => now()->addDays(14),
            'invited_by' => $invitedBy,
        ]);
    }

    /** Returns the plain code so it can be emailed; only the hash is stored. */
    public function freshCode(): string
    {
        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_hash'       => Hash::make($code),
            'otp_expires_at' => now()->addMinutes(15),
            'otp_attempts'   => 0,
            'otp_sent_at'    => now(),
        ]);

        return $code;
    }

    public function codeMatches(string $code): bool
    {
        return $this->otp_hash
            && $this->otp_expires_at?->isFuture()
            && Hash::check($code, $this->otp_hash);
    }

    public function isUsable(): bool
    {
        return ! $this->consumed_at && $this->expires_at->isFuture();
    }

    public function isLockedOut(): bool
    {
        return $this->otp_attempts >= self::MAX_ATTEMPTS;
    }
}
