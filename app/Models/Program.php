<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'slug', 'price_cents', 'summary', 'is_active', 'sort_order'];

    protected function casts(): array
    {
        return [
            'is_active'   => 'boolean',
            'price_cents' => 'integer',
        ];
    }

    public function classSessions(): HasMany
    {
        return $this->hasMany(ClassSession::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function getPriceAttribute(): float
    {
        return $this->price_cents / 100;
    }

    /** "$1,495" */
    public function getPriceLabelAttribute(): string
    {
        return '$' . number_format($this->price_cents / 100);
    }
}
