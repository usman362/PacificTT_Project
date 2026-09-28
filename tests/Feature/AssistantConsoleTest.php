<?php

namespace Tests\Feature;

use App\Mail\InstructorInvitationMail;
use App\Models\Lead;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class AssistantConsoleTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function assistant(): User
    {
        return User::create([
            'name' => 'Assistant', 'email' => 'assistant@example.com',
            'password' => 'secret-pass-123', 'role' => 'assistant', 'is_active' => true,
        ]);
    }

    public function test_the_assistant_console_renders_without_owner_settings(): void
    {
        $this->actingAs($this->assistant())->get('/admin')
            ->assertOk()
            ->assertSee('Assistant Command Center', false)
            ->assertDontSee('id="settings"', false);
    }

    public function test_only_the_owner_can_change_settings(): void
    {
        $this->actingAs($this->assistant())
            ->postJson('/admin/settings/save', ['key' => 'contact_phone', 'value' => 'x'])
            ->assertForbidden();

        $this->actingAs(User::where('role', 'owner')->first())
            ->postJson('/admin/settings/save', ['key' => 'contact_phone', 'value' => '1 (800) 997-4607'])
            ->assertOk();
    }

    public function test_a_deactivated_account_cannot_sign_in(): void
    {
        $user = $this->assistant();
        $user->update(['is_active' => false]);

        $this->post('/admin/login', ['email' => $user->email, 'password' => 'secret-pass-123'])
            ->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_a_mission_counts_as_focus_when_completed_and_noise_when_missed(): void
    {
        $this->actingAs($this->assistant());
        $owner = 'user:'.User::where('role', 'owner')->value('id');

        foreach (['Done in time', 'Left too late'] as $title) {
            $this->postJson('/admin/live/missions', [
                'title' => $title, 'owner' => $owner, 'department' => 'Operations',
                'deadline' => now()->addHour()->toIso8601String(),
            ])->assertOk();
        }

        $done = Mission::where('title', 'Done in time')->first();
        $late = Mission::where('title', 'Left too late')->first();

        $this->patchJson("/admin/live/missions/{$done->id}", ['action' => 'complete'])->assertOk();

        // The deadline passes with the second one still open.
        $late->update(['deadline' => now()->subMinute()]);
        $state = $this->getJson('/admin/live')->assertOk()->json();

        $statuses = collect($state['tasks'])->pluck('status', 'title');
        $this->assertSame('complete', $statuses['Done in time']);
        $this->assertSame('noise', $statuses['Left too late']);

        // Noise cannot be completed after the fact, only rescheduled.
        $this->patchJson("/admin/live/missions/{$late->id}", ['action' => 'complete'])->assertStatus(422);
        $this->patchJson("/admin/live/missions/{$late->id}", [
            'action' => 'reschedule', 'deadline' => now()->addDay()->toIso8601String(),
        ])->assertOk();
        $this->assertSame('open', $late->fresh()->status);
    }

    public function test_missions_only_go_to_active_registered_people(): void
    {
        $this->actingAs($this->assistant())
            ->postJson('/admin/live/missions', [
                'title' => 'x', 'owner' => 'user:9999', 'department' => 'Operations',
                'deadline' => now()->addHour()->toIso8601String(),
            ])->assertStatus(422);
    }

    public function test_the_onboarding_invitation_is_emailed_with_the_offered_rate(): void
    {
        Mail::fake();

        $this->actingAs($this->assistant())
            ->postJson('/admin/instructors/invite', [
                'name' => 'Marco Ruiz', 'email' => 'marco@example.com', 'rate' => 1350,
            ])->assertOk();

        Mail::assertSent(InstructorInvitationMail::class, fn ($m) => $m->hasTo('marco@example.com')
            && $m->invite->daily_rate_cents === 135000);
    }

    public function test_a_new_lead_saves_its_program_from_the_form_label(): void
    {
        $this->actingAs($this->assistant())
            ->postJson('/admin/leads/save', [
                'id' => null, 'name' => 'Lead', 'phone' => '555', 'program' => 'Advanced — $2,500',
                'status' => 'New', 'source' => 'Google',
            ])->assertOk();

        $this->assertSame('advanced-plc-automation', Lead::first()->program->slug);
    }
}
