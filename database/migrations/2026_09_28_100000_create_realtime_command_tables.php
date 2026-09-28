<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Who may sign in, and to which screen. The first account is the owner.
        if (! Schema::hasColumn('users', 'role')) {
            Schema::table('users', function (Blueprint $t) {
                $t->string('role', 20)->default('assistant')->after('email');
                $t->boolean('is_active')->default(true)->after('role');
            });
            DB::table('users')->orderBy('id')->limit(1)->update(['role' => 'owner']);
        }

        // The rate offered in the invitation is what the instructor sees and signs.
        if (! Schema::hasColumn('instructor_invitations', 'daily_rate_cents')) {
            Schema::table('instructor_invitations', function (Blueprint $t) {
                $t->unsignedInteger('daily_rate_cents')->default(120000)->after('name');
            });
        }

        // Numbers the assistant publishes to the owner and staff screens.
        // Append-only: the latest row is what is shown, earlier rows are history.
        Schema::create('published_metrics', function (Blueprint $t) {
            $t->id();
            $t->unsignedInteger('cash_cents')->default(0);
            $t->unsignedInteger('enrollments')->default(0);
            $t->unsignedTinyInteger('utilization')->default(0);
            $t->unsignedTinyInteger('attendance')->default(0);
            $t->unsignedTinyInteger('completion')->default(0);
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        Schema::create('company_updates', function (Blueprint $t) {
            $t->id();
            $t->string('message', 500);
            $t->string('type', 20);          // priority, alert, success, general
            $t->string('department', 40);
            $t->foreignId('published_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
        });

        // Mission Focus: planned work with a deadline. Completed in time it counts
        // as Focus; left open past its deadline it becomes Noise.
        Schema::create('missions', function (Blueprint $t) {
            $t->id();
            $t->string('title', 200);
            $t->string('owner_type', 20);    // user or instructor
            $t->unsignedBigInteger('owner_id');
            $t->string('owner_name');         // as it was when assigned
            $t->string('department', 40);
            $t->dateTime('deadline');
            $t->string('status', 10)->default('open');   // open, complete, noise
            $t->dateTime('completed_at')->nullable();
            $t->foreignId('assigned_by')->nullable()->constrained('users')->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'deadline']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('missions');
        Schema::dropIfExists('company_updates');
        Schema::dropIfExists('published_metrics');
        if (Schema::hasColumn('instructor_invitations', 'daily_rate_cents')) {
            Schema::table('instructor_invitations', fn (Blueprint $t) => $t->dropColumn('daily_rate_cents'));
        }
        if (Schema::hasColumn('users', 'role')) {
            Schema::table('users', fn (Blueprint $t) => $t->dropColumn(['role', 'is_active']));
        }
    }
};
