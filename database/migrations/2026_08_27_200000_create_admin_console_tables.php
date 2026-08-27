<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Instructors drive calendar capacity: each available instructor adds
        // `seats_per_session` seats to a session they can cover.
        if (!Schema::hasTable('instructors')) Schema::create('instructors', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('email')->nullable();
            $t->string('phone')->nullable();
            $t->unsignedInteger('daily_rate_cents')->default(120000);
            $t->enum('status', ['active', 'inactive'])->default('active');
            $t->enum('courses', ['both', 'core', 'advanced'])->default('both');
            $t->timestamps();
        });

        // One row per operating weekday, per instructor.
        if (!Schema::hasTable('instructor_availability')) Schema::create('instructor_availability', function (Blueprint $t) {
            $t->id();
            $t->foreignId('instructor_id')->constrained()->cascadeOnDelete();
            $t->unsignedTinyInteger('weekday');          // 1 = Mon … 6 = Sat
            $t->boolean('is_available')->default(true);
            $t->time('starts_at')->default('08:00');
            $t->time('ends_at')->default('20:00');
            $t->unique(['instructor_id', 'weekday']);
        });

        if (!Schema::hasTable('leads')) Schema::create('leads', function (Blueprint $t) {
            $t->id();
            $t->string('name');
            $t->string('phone');
            $t->string('email')->nullable();
            $t->string('electrical_experience')->nullable();
            $t->string('plc_experience')->nullable();
            $t->foreignId('program_id')->nullable()->constrained()->nullOnDelete();
            $t->date('preferred_date')->nullable();
            $t->string('preferred_session')->nullable();
            $t->string('source')->default('Other');
            $t->enum('status', [
                'new', 'contacted', 'interested', 'enrollment_started', 'paid', 'lost',
            ])->default('new');
            $t->dateTime('follow_up_at')->nullable();
            $t->text('notes')->nullable();
            $t->foreignId('enrollment_id')->nullable()->constrained()->nullOnDelete();
            $t->timestamps();
            $t->index(['status', 'follow_up_at']);
        });

        // Zelle sits alongside Stripe but is never "paid" until staff confirm it.
        Schema::table('payments', function (Blueprint $t) {
            if (!Schema::hasColumn('payments', 'method')) { $t->string('method')->default('stripe')->after('type'); }
            if (!Schema::hasColumn('payments', 'reference_note')) { $t->string('reference_note')->nullable()->after('card_last4'); }
            if (!Schema::hasColumn('payments', 'verified_by')) { $t->foreignId('verified_by')->nullable()->after('status')->constrained('users')->nullOnDelete(); }
            if (!Schema::hasColumn('payments', 'verified_at')) { $t->timestamp('verified_at')->nullable()->after('verified_by'); }
        });

        // The student signs; then an authorised PTT representative accepts.
        Schema::table('waivers', function (Blueprint $t) {
            if (!Schema::hasColumn('waivers', 'staff_name')) { $t->string('staff_name')->nullable()->after('signed_at'); }
            if (!Schema::hasColumn('waivers', 'staff_signature')) { $t->string('staff_signature')->nullable()->after('staff_name'); }
            if (!Schema::hasColumn('waivers', 'staff_accepted_on')) { $t->date('staff_accepted_on')->nullable()->after('staff_signature'); }
            if (!Schema::hasColumn('waivers', 'staff_user_id')) { $t->foreignId('staff_user_id')->nullable()->after('staff_accepted_on')->constrained('users')->nullOnDelete(); }
            if (!Schema::hasColumn('waivers', 'needs_review')) { $t->boolean('needs_review')->default(false)->after('staff_user_id'); }
        });

        Schema::table('certificates', function (Blueprint $t) {
            if (!Schema::hasColumn('certificates', 'enrollment_id')) { $t->foreignId('enrollment_id')->nullable()->after('id')->constrained()->nullOnDelete(); }
            if (!Schema::hasColumn('certificates', 'instructor_id')) { $t->foreignId('instructor_id')->nullable()->after('enrollment_id')->constrained()->nullOnDelete(); }
            if (!Schema::hasColumn('certificates', 'status')) { $t->enum('status', ['ready','issued','revoked'])->default('issued')->after('is_public'); }
            if (!Schema::hasColumn('certificates', 'issued_at')) { $t->timestamp('issued_at')->nullable()->after('status'); }
        });
    }

    public function down(): void
    {
        Schema::table('certificates', fn (Blueprint $t) => $t->dropConstrainedForeignId('enrollment_id'));
        Schema::table('certificates', fn (Blueprint $t) => $t->dropConstrainedForeignId('instructor_id'));
        Schema::table('certificates', fn (Blueprint $t) => $t->dropColumn(['status', 'issued_at']));
        Schema::table('waivers', fn (Blueprint $t) => $t->dropConstrainedForeignId('staff_user_id'));
        Schema::table('waivers', fn (Blueprint $t) => $t->dropColumn(['staff_name', 'staff_signature', 'staff_accepted_on', 'needs_review']));
        Schema::table('payments', fn (Blueprint $t) => $t->dropConstrainedForeignId('verified_by'));
        Schema::table('payments', fn (Blueprint $t) => $t->dropColumn(['method', 'reference_note', 'verified_at']));
        Schema::dropIfExists('leads');
        Schema::dropIfExists('instructor_availability');
        Schema::dropIfExists('instructors');
    }
};
