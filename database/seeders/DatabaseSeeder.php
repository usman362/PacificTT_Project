<?php

namespace Database\Seeders;

use App\Models\Certificate;
use App\Models\Program;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Admin login ──────────────────────────────────────────────────
        User::updateOrCreate(
            ['email' => 'admin@pacifictradetech.com'],
            [
                'name'     => 'PTT Admin',
                'password' => Hash::make('ChangeMe!2026'),
            ]
        );

        // ── Programs (prices match the original prototype) ───────────────
        $programs = [
            [
                'name'        => 'PLC + Electrical Controls',
                'slug'        => 'plc-electrical-controls',
                'price_cents' => 149500,
                'summary'     => 'Core PLC and electrical controls training for entry to intermediate technicians.',
                'sort_order'  => 1,
            ],
            [
                'name'        => 'Advanced PLC / Automation',
                'slug'        => 'advanced-plc-automation',
                'price_cents' => 250000,
                'summary'     => 'Advanced automation, networking and troubleshooting for working technicians.',
                'sort_order'  => 2,
            ],
        ];

        foreach ($programs as $p) {
            Program::updateOrCreate(['slug' => $p['slug']], $p);
        }

        // ── One sample public certificate, so verification has a record ──
        Certificate::updateOrCreate(
            ['certificate_number' => 'PTT-2026-00124'],
            [
                'student_name' => 'Alex Martinez',
                'course'       => 'Advanced PLC / Automation',
                'completed_on' => '2026-08-08',
                'is_public'    => true,
            ]
        );
    }
}
