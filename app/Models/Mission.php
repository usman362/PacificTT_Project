<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mission extends Model
{
    public const DEPARTMENTS = ['Operations', 'Enrollment', 'Training', 'Finance', 'Growth'];

    protected $fillable = [
        'title', 'owner_type', 'owner_id', 'owner_name', 'department',
        'deadline', 'status', 'completed_at', 'assigned_by',
    ];

    protected function casts(): array
    {
        return ['deadline' => 'datetime', 'completed_at' => 'datetime'];
    }

    /**
     * An open mission whose deadline has passed is Noise. Settled on read, so
     * every screen agrees without a scheduler having to run.
     */
    public static function settleExpired(): int
    {
        return static::where('status', 'open')
            ->where('deadline', '<', now())
            ->update(['status' => 'noise', 'updated_at' => now()]);
    }

    /**
     * People who may be given a mission: active sign-in accounts and active
     * instructors, each keyed "user:ID" / "instructor:ID".
     */
    public static function assignableOwners(): array
    {
        $users = User::where('is_active', true)->orderBy('name')->get()
            ->map(fn ($u) => ['id' => 'user:'.$u->id, 'name' => $u->name, 'role' => $u->roleLabel()]);

        $instructors = Instructor::where('status', 'active')->orderBy('name')->get()
            ->map(fn ($i) => ['id' => 'instructor:'.$i->id, 'name' => $i->name, 'role' => 'Instructor']);

        return $users->concat($instructors)->sortBy('name', SORT_NATURAL | SORT_FLAG_CASE)->values()->all();
    }
}
