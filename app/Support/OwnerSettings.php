<?php

namespace App\Support;

use App\Models\Setting;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Schema;

/**
 * Owner Settings live in the settings table and take effect across the whole
 * system: they are laid over config() on every request, so the checkout, seat
 * holds and capacity engine all read the owner's values without knowing where
 * they came from. Anything the owner has not set falls back to .env / config.
 */
class OwnerSettings
{
    public const TARGETS = [
        'target_enrollments' => 24,
        'target_utilization' => 80,
        'target_margin'      => 60,
        'target_reserve'     => 6,
        'target_leads'       => 200,
        'target_noise'       => 15,
    ];

    public const MERCHANTS = ['Stripe', 'PayPal', 'Zelle', 'Other'];

    public const MODES = ['Live', 'Test', 'Disabled'];

    /** Encrypted at rest; never sent back to the browser in full. */
    public const SECRETS = ['stripe_key', 'stripe_secret', 'stripe_webhook_secret'];

    public static function apply(): void
    {
        try {
            if (! Schema::hasTable('settings')) {
                return;
            }
        } catch (\Throwable) {
            return;   // no database yet (fresh install, artisan before migrate)
        }

        foreach ([
            'seats_per_instructor' => 'ptt.seats_per_instructor',
            'deposit_percent'      => 'ptt.deposit_percent',
            'seat_hold_minutes'    => 'ptt.seat_hold_minutes',
        ] as $key => $configKey) {
            $v = Setting::get($key);
            if ($v !== null && $v !== '') {
                config([$configKey => (int) $v]);
            }
        }

        foreach (self::SECRETS as $key) {
            $v = self::secret($key);
            if ($v) {
                config(['services.stripe.'.substr($key, 7) => $v]);
            }
        }

        // Card checkout runs only when the owner's merchant is Stripe and
        // payments are not switched off.
        $merchant = Setting::get('payment_merchant', 'Stripe');
        $mode     = Setting::get('payment_mode', 'Live');
        if ($merchant !== 'Stripe' || $mode === 'Disabled') {
            config(['services.stripe.key' => null, 'services.stripe.secret' => null]);
        }
    }

    public static function secret(string $key): ?string
    {
        $v = Setting::get($key);
        if (! $v) {
            return null;
        }
        try {
            return Crypt::decryptString($v);
        } catch (\Throwable) {
            return null;
        }
    }

    public static function putSecret(string $key, string $value): void
    {
        Setting::put($key, Crypt::encryptString($value));
    }

    /** "sk_live_…4f3a" — enough to recognise a key, useless to anyone else. */
    public static function masked(string $key): ?string
    {
        $v = self::secret($key) ?? config('services.stripe.'.substr($key, 7));
        if (! $v) {
            return null;
        }
        $prefix = preg_match('/^([a-z]+_(?:live|test)_)/', $v, $m) ? $m[1] : substr($v, 0, 6);

        return $prefix.'…'.substr($v, -4);
    }

    public static function targets(): array
    {
        $out = [];
        foreach (self::TARGETS as $key => $default) {
            $out[$key] = (float) (Setting::get($key) ?? $default);
        }

        return $out;
    }

    public static function controls(): array
    {
        return [
            'seats_per_instructor' => (int) config('ptt.seats_per_instructor'),
            'sessions_per_day'     => count(config('ptt.session_slots')),
            'deposit_percent'      => (int) config('ptt.deposit_percent'),
            'seat_hold_minutes'    => (int) config('ptt.seat_hold_minutes'),
            'payment_merchant'     => Setting::get('payment_merchant', 'Stripe'),
            'payment_mode'         => Setting::get('payment_mode', 'Live'),
            'stripe_key'           => self::masked('stripe_key'),
            'stripe_secret'        => self::masked('stripe_secret'),
            'stripe_webhook_secret'=> self::masked('stripe_webhook_secret'),
        ];
    }
}
