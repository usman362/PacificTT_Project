<?php

namespace Tests\Feature;

use App\Models\OwnerObjective;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OwnerDashboardTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    private function owner(): User
    {
        return User::where('role', 'owner')->firstOrFail();
    }

    public function test_the_owner_lands_on_the_operations_dashboard(): void
    {
        $this->post('/admin/login', ['email' => 'admin@pacifictradetech.com', 'password' => 'ChangeMe!2026'])
            ->assertRedirect(route('admin.owner.dashboard'));

        $this->get('/admin/owner')->assertOk()->assertSee('Owner Settings', false);
    }

    public function test_an_assistant_cannot_open_the_owner_dashboard(): void
    {
        $assistant = User::create(['name' => 'A', 'email' => 'a@example.com', 'password' => 'secret-pass-1',
                                   'role' => 'assistant', 'is_active' => true]);

        $this->actingAs($assistant)->get('/admin/owner')->assertForbidden();
        $this->actingAs($assistant)->postJson('/admin/owner/controls', [])->assertForbidden();
    }

    public function test_owner_controls_drive_the_system_and_keys_are_encrypted(): void
    {
        $this->actingAs($this->owner())->postJson('/admin/owner/controls', [
            'seats_per_instructor' => 6, 'deposit_percent' => 25, 'seat_hold_minutes' => 15,
            'payment_merchant' => 'Stripe', 'payment_mode' => 'Test',
            'stripe_key' => 'pk_test_abc123', 'stripe_secret' => 'sk_test_def456',
        ])->assertOk()->assertJsonPath('controls.stripe_secret', 'sk_test_…f456');

        $this->assertStringNotContainsString('sk_test_def456', (string) Setting::find('stripe_secret')->value);

        \App\Support\OwnerSettings::apply();
        $this->assertSame(6, config('ptt.seats_per_instructor'));
        $this->assertSame(15, config('ptt.seat_hold_minutes'));
        $this->assertSame('sk_test_def456', config('services.stripe.secret'));

        // The public page receives the owner's hold time and key.
        $this->get('/')->assertSee('holdSeconds: 900', false)->assertSee('pk_test_abc123', false);
    }

    public function test_keys_must_match_the_payment_mode(): void
    {
        $this->actingAs($this->owner())->postJson('/admin/owner/controls', [
            'seats_per_instructor' => 8, 'deposit_percent' => 30, 'seat_hold_minutes' => 10,
            'payment_merchant' => 'Stripe', 'payment_mode' => 'Live', 'stripe_secret' => 'sk_test_def456',
        ])->assertStatus(422);
    }

    public function test_disabled_payments_switch_card_checkout_off(): void
    {
        $this->actingAs($this->owner())->postJson('/admin/owner/controls', [
            'seats_per_instructor' => 8, 'deposit_percent' => 30, 'seat_hold_minutes' => 10,
            'payment_merchant' => 'Stripe', 'payment_mode' => 'Disabled', 'stripe_key' => 'pk_live_abc123',
        ])->assertOk();

        \App\Support\OwnerSettings::apply();
        $this->assertNull(config('services.stripe.key'));
    }

    public function test_an_objective_missed_past_its_deadline_is_noise_until_reopened(): void
    {
        $this->actingAs($this->owner());

        $id = $this->postJson('/admin/owner/objectives', [
            'title' => 'Approve ad spend', 'category' => 'Finance', 'deadline' => now()->addHour()->toIso8601String(),
        ])->assertCreated()->json('id');

        OwnerObjective::find($id)->update(['deadline' => now()->subMinute()]);
        $this->assertSame('missed', collect($this->getJson('/admin/owner/objectives')->json())->firstWhere('id', $id)['status']);

        $this->patchJson("/admin/owner/objectives/{$id}", ['action' => 'complete'])->assertStatus(422);
        $this->patchJson("/admin/owner/objectives/{$id}", [
            'action' => 'reopen', 'deadline' => now()->addDay()->toIso8601String(),
        ])->assertOk()->assertJsonPath('status', 'open');
        $this->patchJson("/admin/owner/objectives/{$id}", ['action' => 'complete'])->assertOk()->assertJsonPath('status', 'complete');
    }
}
