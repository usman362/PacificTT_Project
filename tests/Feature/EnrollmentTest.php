<?php

namespace Tests\Feature;

use App\Models\Certificate;
use App\Models\Enrollment;
use App\Models\Program;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EnrollmentTest extends TestCase
{
    use RefreshDatabase;

    protected bool $seed = true;

    public function test_the_enrollment_page_loads(): void
    {
        $this->get('/')
            ->assertStatus(200)
            ->assertSee('Compare Programs', false);
    }

    public function test_a_student_can_enrol_and_receives_a_reference(): void
    {
        $program = Program::first();

        $response = $this->postJson('/api/enrollments', [
            'name'                  => 'Test Student',
            'phone'                 => '(555) 010-2030',
            'email'                 => 'test@example.com',
            'electrical_experience' => 'None — starting from zero',
            'plc_experience'        => 'None',
            'program_id'            => $program->id,
            'session_slot'          => config('ptt.session_slots')[0],
            'preferred_date'        => $this->nextOpenDay()->toDateString(),
        ]);

        $response->assertStatus(201)
            ->assertJsonPath('ok', true);

        $this->assertDatabaseHas('enrollments', [
            'email'  => 'test@example.com',
            'status' => 'started',
        ]);

        $this->assertMatchesRegularExpression(
            '/^PTT-\d{4}-\d{5}$/',
            Enrollment::first()->reference
        );
    }

    public function test_a_sunday_is_refused(): void
    {
        $program = Program::first();
        $sunday  = Carbon::today()->next(Carbon::SUNDAY);

        $this->getJson('/api/availability?' . http_build_query([
            'program_id'   => $program->id,
            'session_slot' => config('ptt.session_slots')[0],
            'date'         => $sunday->toDateString(),
        ]))
            ->assertStatus(200)
            ->assertJsonPath('open', false)
            ->assertJsonPath('reason', 'Sunday classes are not available. Please select Monday–Saturday.');
    }

    public function test_a_public_certificate_can_be_verified(): void
    {
        Certificate::create([
            'certificate_number' => 'PTT-2026-00999',
            'student_name'       => 'Verified Graduate',
            'course'             => 'PLC + Electrical Controls',
            'completed_on'       => '2026-01-15',
            'is_public'          => true,
        ]);

        $this->getJson('/api/verify?q=PTT-2026-00999')
            ->assertStatus(200)
            ->assertJsonPath('found', true)
            ->assertJsonPath('certificate.student_name', 'Verified Graduate');
    }

    private function nextOpenDay(): Carbon
    {
        $d = Carbon::today()->addDays(7);
        while ($d->dayOfWeek === Carbon::SUNDAY) {
            $d->addDay();
        }

        return $d;
    }
}
