# Pacific Trade Tech — Enrollment System

Laravel backend for the Industrial Controls Academy enrollment funnel.
The original single-file prototype is preserved in `_original/`.

## What it does

- **8-step enrollment funnel** — saves to the database and issues a reference (`PTT-2026-00001`)
- **Real seat availability** — live counts per program + session + date, Sundays closed
- **Seat holds** — a 10-minute hold, enforced server-side; expired holds free their seat automatically
- **Waiver** — stored with the drawn signature (PNG), IP and timestamp
- **Payments** — Stripe, deposit (30%) or full tuition
- **Emails** — confirmation to the student, alert to the office
- **Admin panel** — enrollments, filters, CSV export, waiver + signature, certificate registry
- **Graduate verification** — public certificate lookup backed by the registry

## Requirements

PHP 8.2+, Composer, MySQL 5.7+/8, and the usual Laravel extensions.

## Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
# point DB_* at your database, then:
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

Admin sign-in: `/admin` — `admin@pacifictradetech.com` / `ChangeMe!2026`
**Change that password before the site goes anywhere public.**

## Stripe

Card details are collected by **Stripe Elements** in the browser and exchanged for a
PaymentIntent. No card number, expiry or CVC ever reaches this application or its
database — only Stripe's identifiers and the brand/last four are stored.

Add to `.env`:

```
STRIPE_KEY=pk_live_...
STRIPE_SECRET=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Then add a webhook endpoint in the Stripe dashboard pointing at:

```
https://your-domain.com/stripe/webhook
```

subscribed to `payment_intent.succeeded` and `payment_intent.payment_failed`.

**The webhook is the authority on payment state**, not the browser. The browser's
confirm call is a convenience so the student sees a result immediately; both paths
run the same idempotent handler, so neither double-charges nor double-emails.

Until keys are present the checkout tells the student payments are not configured
and asks them to call the office — it does not fail silently.

## Email

`MAIL_MAILER=log` writes mail to `storage/logs/laravel.log` for local testing.
For production set real SMTP credentials and `MAIL_ADMIN_ADDRESS` for the office alert.

## Configuration

`config/ptt.php`:

| Key | Meaning |
|---|---|
| `session_slots` | the three timetable slots offered |
| `default_capacity` | seats per dated class (12) |
| `seat_hold_minutes` | checkout hold window (10) |
| `deposit_percent` | deposit portion (30) |
| `closed_weekdays` | `[0]` — Sunday |

Dated classes are created on demand: a date nobody has booked reports full
capacity, so no one has to pre-create a calendar. An admin can then change a
specific class's capacity or deactivate it.

## Deploying to cPanel

The host keeps the application core **outside** the web root:

```
/home4/<user>/private       the whole repository (core) — never web-reachable
/home4/<user>/public_html   only the contents of public/
```

`public/index.php` finds the core by itself — one level up in development,
`../private` on the host — so no paths need editing.

**First time**

1. `cd ~ && git clone <repo-url> private`
2. `cd ~/private && cp .env.example .env`, then set `APP_ENV=production`,
   `APP_DEBUG=false`, `APP_URL=https://<domain>` (no `/public`), DB and mail.
3. `composer install --no-dev --optimize-autoloader && php artisan key:generate`
4. `php artisan migrate --force --seed` — creates the admin account and the
   starting programmes; change the admin password straight after.
5. `bash deploy/deploy.sh` — copies `public/` into `public_html` and links uploads.
6. Make `storage/` and `bootstrap/cache/` writable by PHP.

**Every update**

```bash
bash ~/private/deploy/deploy.sh
```

It pulls, installs dependencies, migrates, copies `public/` into
`public_html` (it never deletes anything already there), links
`public_html/storage` to the core's uploads, and rebuilds caches. Override the
folders with `PUBLIC_HTML=...` or the Composer command with `COMPOSER=...`.

## Notes / limits

- **Seat holds are enforced on the server.** The on-screen countdown is cosmetic;
  the API re-checks the real expiry on every waiver and payment call.
- Seat counts are derived from live enrolments, never stored, so they cannot drift
  and no cleanup job is needed.
- The signature is validated as a real PNG (magic number, size bounds) before it is
  written. It does **not** verify that the canvas contains actual ink — the browser
  requires a stroke, but a determined caller could post a blank-but-valid PNG.
- Event registration links and the gift registry URL in the original prototype are
  unrelated to this system.
