<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Payment extends Model
{
    use HasFactory;

    protected $fillable = [
        'enrollment_id', 'type', 'amount_cents', 'currency',
        'stripe_payment_intent_id', 'card_brand', 'card_last4',
        'status', 'failure_message', 'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'amount_cents' => 'integer',
            'paid_at'      => 'datetime',
        ];
    }

    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(Enrollment::class);
    }

    public function getAmountLabelAttribute(): string
    {
        return '$' . number_format($this->amount_cents / 100, 2);
    }
}
