<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class StaffDisplayTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_signed_in_staff_can_open_the_display(): void
    {
        $this->actingAs(User::first())->get('/staff')->assertOk()->assertSee('Know the Mission', false);
        $this->actingAs(User::first())->getJson('/staff/live')->assertOk()->assertJsonMissingPath('owners');
    }

    public function test_guests_need_the_owners_display_link(): void
    {
        $this->get('/staff')->assertRedirect(route('admin.login'));
        $this->getJson('/staff/live')->assertForbidden();
        $this->get('/staff?display=guess')->assertNotFound();
    }

    public function test_a_new_display_link_retires_the_old_one(): void
    {
        $owner = User::where('role', 'owner')->first();
        $old = $this->actingAs($owner)->postJson('/admin/owner/display-link')->assertOk()->json('url');
        $new = $this->actingAs($owner)->postJson('/admin/owner/display-link')->assertOk()->json('url');
        auth()->logout();

        $this->get($new)->assertOk();
        $this->get($old)->assertNotFound();
        parse_str(parse_url($new, PHP_URL_QUERY), $q);
        $this->getJson('/staff/live?display='.$q['display'])->assertOk();
    }
}
