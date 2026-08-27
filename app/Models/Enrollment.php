<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class Enrollment extends Model
{
    use HasFactory;

    /** Statuses that mean the student still occupies (or has claimed) a seat. */
    public const SEAT_HOLDING_STATUSES = ['waiver_signed', 'deposit_paid', 'paid'];

    protected $fillable = [
        'reference', 'name', 'phone', 'email',
        'electrical_experience', 'plc_experience',
        'program_id', 'class_session_id', 'preferred_date',
        'status', 'seat_hold_expires_at', 'tuition_cents',
        'ip_address', 'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'preferred_date'       => 'date',
            'seat_hold_expires_at' => 'datetime',
            'tuition_cents'        => 'integer',
        ];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function classSession(): BelongsTo
    {
        return $this->belongsTo(ClassSession::class);
    }

    public function waiver(): HasOne
    {
        return $this->hasOne(Waiver::class);
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function certificate(): HasOne
    {
        return $this->hasOne(Certificate::class);
    }

    /**
     * An enrolment occupies a seat when it has progressed past the waiver, or
     * while a 'started' record is still inside its hold window.
     */
    public function scopeHoldingSeat(Builder $query): Builder
    {
        return $query->where(function (Builder $q) {
            $q->whereIn('status', self::SEAT_HOLDING_STATUSES)
              ->orWhere(function (Builder $inner) {
                  $inner->where('status', 'started')
                        ->whereNotNull('seat_hold_expires_at')
                        ->where('seat_hold_expires_at', '>', now());
              });
        });
    }

    public function isSeatHoldExpired(): bool
    {
        return $this->status === 'started'
            && $this->seat_hold_expires_at !== null
            && $this->seat_hold_expires_at->isPast();
    }

    public function amountPaidCents(): int
    {
        return (int) $this->payments()->where('status', 'succeeded')->sum('amount_cents');
    }

    public function balanceDueCents(): int
    {
        return max(0, (int) $this->tuition_cents - $this->amountPaidCents());
    }

    /**
     * Sequential, human-quotable reference: PTT-2026-00124.
     * Generated inside a transaction by the caller so two enrolments created at
     * the same moment cannot collide.
     */
    public static function nextReference(): string
    {
        $year   = now()->year;
        $prefix = "PTT-{$year}-";

        $last = static::where('reference', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('reference')
            ->value('reference');

        $n = $last ? ((int) substr($last, strlen($prefix))) + 1 : 1;

        return $prefix . str_pad((string) $n, 5, '0', STR_PAD_LEFT);
    }

    /** Total still owed across every enrolment that has paid something but not all. */
    public static function outstandingTotal(): int
    {
        return static::withBalance()->get()->sum(fn (self $e) => $e->balanceCents());
    }

    /** Enrolments carrying a balance (deposit taken, or nothing yet paid). */
    public static function withBalance()
    {
        return static::whereIn('status', ['deposit_paid', 'waiver_signed', 'started']);
    }

    public function paidCents(): int
    {
        return (int) $this->payments()->where('status', 'succeeded')->sum('amount_cents');
    }

    public function balanceCents(): int
    {
        return max(0, (int) $this->tuition_cents - $this->paidCents());
    }

    public function statusLabel(): string
    {
        return match ($this->status) {
            'started'       => 'Started',
            'waiver_signed' => 'Waiver signed',
            'deposit_paid'  => 'Deposit',
            'paid'          => 'Paid',
            'completed'     => 'Completed',
            'cancelled'     => 'Cancelled',
            'abandoned'     => 'Abandoned',
            default         => ucfirst(str_replace('_', ' ', (string) $this->status)),
        };
    }
}
