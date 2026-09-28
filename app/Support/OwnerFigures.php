<?php

namespace App\Support;

use App\Models\Setting;

/**
 * Figures the system cannot measure, entered by the owner on the Operations
 * Dashboard: equipment, costs, cash reserve, quality, satisfaction, safety,
 * owner dependency, growth phase, weekly decisions and the SOP library.
 * Stored as one JSON document; everything else on A1 is computed.
 */
class OwnerFigures
{
    public const SOP_STATUSES = ['Approved', 'Review', 'Draft', 'Founder only'];

    public static function all(): array
    {
        $data = json_decode((string) Setting::get('owner_figures', '{}'), true) ?: [];

        return array_replace_recursive([
            'equipment'         => [],
            'quality'           => ['modules_done' => null, 'modules_total' => null, 'assessments' => null, 'safety' => null],
            'costs'             => [],
            'reserve_cash'      => null,
            'profitable_months' => null,
            'engines'           => ['specialty' => null, 'b2b' => null],
            'satisfaction'      => ['score' => null, 'responses' => null],
            'safety'            => ['last_incident' => null, 'incidents' => null],
            'dependency'        => [],
            'growth'            => ['phase' => 1, 'pct' => null, 'manager_verified' => false],
            'weekly'            => ['sales' => '', 'ops' => '', 'finance' => '', 'wins' => [], 'next' => []],
            'sops'              => [],
        ], $data);
    }

    public static function save(array $changes): array
    {
        $all = self::all();
        foreach ($changes as $k => $v) {
            $all[$k] = $v;   // whole-key replace: lists shrink when rows are removed
        }
        Setting::put('owner_figures', json_encode($all));

        return $all;
    }

    /** This month's cost lines. */
    public static function costs(?string $month = null): array
    {
        return self::all()['costs'][$month ?? now()->format('Y-m')] ?? [];
    }

    /** Per-group validation rules for the edit forms. */
    public static function rules(string $group): array
    {
        return match ($group) {
            'equipment'    => ['equipment' => ['present', 'array', 'max:40'],
                               'equipment.*.name' => ['required', 'string', 'max:80'],
                               'equipment.*.online' => ['required', 'integer', 'min:0', 'max:999'],
                               'equipment.*.total' => ['required', 'integer', 'min:1', 'max:999', 'gte:equipment.*.online']],
            'quality'      => ['modules_done' => ['nullable', 'integer', 'min:0'], 'modules_total' => ['nullable', 'integer', 'min:0'],
                               'assessments' => ['nullable', 'numeric', 'min:0', 'max:100'], 'safety' => ['nullable', 'numeric', 'min:0', 'max:100']],
            'costs'        => ['costs' => ['present', 'array', 'max:40'],
                               'costs.*.name' => ['required', 'string', 'max:80'],
                               'costs.*.amount' => ['required', 'numeric', 'min:0', 'max:100000000']],
            'finance'      => ['reserve_cash' => ['nullable', 'numeric', 'min:0'], 'profitable_months' => ['nullable', 'integer', 'min:0', 'max:600'],
                               'specialty' => ['nullable', 'numeric', 'min:0'], 'b2b' => ['nullable', 'numeric', 'min:0']],
            'satisfaction' => ['score' => ['nullable', 'numeric', 'min:0', 'max:5'], 'responses' => ['nullable', 'integer', 'min:0']],
            'safety'       => ['last_incident' => ['nullable', 'date', 'before_or_equal:today'], 'incidents' => ['nullable', 'integer', 'min:0']],
            'dependency'   => ['dependency' => ['present', 'array', 'max:40'],
                               'dependency.*.name' => ['required', 'string', 'max:80'],
                               'dependency.*.delegated' => ['required', 'boolean']],
            'growth'       => ['phase' => ['required', 'integer', 'min:1', 'max:5'], 'pct' => ['nullable', 'integer', 'min:0', 'max:100'],
                               'manager_verified' => ['required', 'boolean']],
            'weekly'       => ['sales' => ['nullable', 'string', 'max:200'], 'ops' => ['nullable', 'string', 'max:200'],
                               'finance' => ['nullable', 'string', 'max:200'],
                               'wins' => ['present', 'array', 'max:12'], 'wins.*' => ['string', 'max:160'],
                               'next' => ['present', 'array', 'max:12'], 'next.*' => ['string', 'max:160']],
            'sops'         => ['sops' => ['present', 'array', 'max:200'],
                               'sops.*.title' => ['required', 'string', 'max:120'],
                               'sops.*.code' => ['nullable', 'string', 'max:20'],
                               'sops.*.area' => ['nullable', 'string', 'max:40'],
                               'sops.*.pct' => ['required', 'integer', 'min:0', 'max:100'],
                               'sops.*.status' => ['required', 'in:'.implode(',', self::SOP_STATUSES)]],
            default        => abort(404),
        };
    }

    /** Turn a validated form into the keys it replaces. */
    public static function changes(string $group, array $d): array
    {
        return match ($group) {
            'equipment', 'dependency', 'sops' => [$group => array_values($d[$group])],
            'quality'      => ['quality' => $d],
            'costs'        => ['costs' => array_replace(self::all()['costs'], [now()->format('Y-m') => array_values($d['costs'])])],
            'finance'      => ['reserve_cash' => $d['reserve_cash'] ?? null, 'profitable_months' => $d['profitable_months'] ?? null,
                               'engines' => ['specialty' => $d['specialty'] ?? null, 'b2b' => $d['b2b'] ?? null]],
            'satisfaction' => ['satisfaction' => $d],
            'safety'       => ['safety' => $d],
            'growth'       => ['growth' => $d],
            'weekly'       => ['weekly' => ['sales' => $d['sales'] ?? '', 'ops' => $d['ops'] ?? '', 'finance' => $d['finance'] ?? '',
                               'wins' => array_values(array_filter($d['wins'])), 'next' => array_values(array_filter($d['next']))]],
        };
    }
}
