# How to use this project

Two audiences: whoever runs the server (you), and whoever runs the school
(the office staff who live in the admin panel).

---

## 1. Starting it up (local)

```bash
cd ~/Sites/PacificTT_Project
php artisan serve --host=127.0.0.1 --port=8899
```

| What | Where |
|---|---|
| Public site | http://127.0.0.1:8899 |
| Admin panel | http://127.0.0.1:8899/admin |

Admin sign-in: `admin@pacifictradetech.com` / `ChangeMe!2026`

Stop the server with `Ctrl+C`, or `pkill -f "artisan serve"`.

If MySQL isn't running: `brew services start mysql`.

---

## 2. What a student does

1. **Compare Programs** — picks a program, which jumps them into the form.
2. **Eight steps** — name, phone, email, electrical experience, PLC experience,
   program, session, start date. The bar at the top tracks progress.
3. **Availability** — once program + session + date are chosen the page asks the
   server how many seats are actually left on that day.
   Sundays and past dates are refused.
4. **COMPLETE** — the enrolment is saved and they get a reference (`PTT-2026-00001`).
   **A 10-minute seat hold starts here.**
5. **Waiver** — legal name, address, emergency contact, the agreement tick, and a
   signature drawn with finger or mouse. Signing renews the hold to a fresh 10 minutes.
6. **Checkout** — pay in full, or a 30% deposit with the balance due on site.
   Card details are typed into Stripe's own fields.
7. **Done** — confirmation email to the student, alert to the office.

If the 10 minutes run out at any point, an overlay tells them the hold expired and
they restart. The seat goes back into the pool automatically.

---

## 3. What the office does

### Dashboard (`/admin`)
Confirmed enrolments, how many are mid-way, total collected, and recent activity.

### Enrollments (`/admin/enrollments`)
- **Search** by name, email, phone or reference
- **Filter** by status, program, or date range
- **Export CSV** — exports exactly what the current filter shows, not everything

Statuses:

| Status | Meaning |
|---|---|
| Started | Form done, waiver not signed yet |
| Waiver signed | Waiver in, payment not made |
| Deposit | 30% paid, balance due on site |
| Paid | Paid in full |
| Cancelled / Abandoned | Set by hand when someone drops out |

Click a reference to open a student: their details, payments (with card brand and
last four), balance due, the full waiver, and the signature they drew.
You can change the status from that page — useful when someone pays by cash or
cheque on the day.

### Certificates (`/admin/certificates`)
Add a graduate — number, name, course, completion date, optional photo.
Tick **Publicly verifiable** and they can immediately be looked up by anyone using
the *Verify a Graduate* search on the public site. Untick it to keep a record
internal. Numbers are stored uppercase and must be unique.

---

## 4. Settings you can change

`config/ptt.php`:

| Setting | Default | What it controls |
|---|---|---|
| `session_slots` | 3 slots | The session times offered in the form |
| `default_capacity` | 12 | Seats per class day |
| `seat_hold_minutes` | 10 | How long checkout holds a seat |
| `deposit_percent` | 30 | Deposit size |
| `closed_weekdays` | `[0]` | `0` = Sunday closed |

Programs and prices are rows in the database (`programs` table) — change the price
there and the site, the checkout and the emails all follow.

Class days are created the first time someone books them. To cap or close a
specific day, edit its row in `class_sessions` (`capacity`, or `is_active = 0`).

---

## 5. Turning payments on

Payments are the one piece that is built but not switched on, because it needs the
client's Stripe account.

1. In the Stripe dashboard, copy the publishable and secret keys.
2. Put them in `.env`:
   ```
   STRIPE_KEY=pk_live_...
   STRIPE_SECRET=sk_live_...
   ```
3. Add a webhook endpoint pointing at `https://your-domain.com/stripe/webhook`,
   subscribed to `payment_intent.succeeded` and `payment_intent.payment_failed`.
   Copy its signing secret into `STRIPE_WEBHOOK_SECRET`.
4. `php artisan config:clear`

Until then the checkout tells the student payments aren't set up and to ring the
office — it does not fail silently or pretend to take money.

**Card numbers never reach this server.** They go from the student's browser
straight to Stripe. We only ever store Stripe's payment ID and the brand/last four.

---

## 6. Turning email on

Right now `MAIL_MAILER=log`, so emails are written to
`storage/logs/laravel.log` instead of being sent — handy for testing.

For real mail, set the SMTP details in `.env` and:

```
MAIL_MAILER=smtp
MAIL_ADMIN_ADDRESS=office@pacifictradetech.com
```

Two emails go out on payment: a confirmation to the student, and an alert to
`MAIL_ADMIN_ADDRESS` with the student's details and emergency contact.

---

## 7. Clearing the demo data

The system currently has sample students in it so the admin panel has something
to show. Before going live:

```bash
php artisan tinker --execute="
App\Models\Payment::truncate();
App\Models\Waiver::truncate();
App\Models\Enrollment::query()->delete();
App\Models\ClassSession::query()->delete();
"
```

Programs, certificates and the admin login are left alone.
Also delete the demo signatures: `rm -f storage/app/public/signatures/*-demo.png`

---

## 8. Going live

See **Deploying to cPanel** in `README.md`. In short: the project sits outside the
web root, the domain points at `public/`, then `composer install --no-dev`,
`php artisan migrate --force`, `storage:link`, and cache the config/routes/views.

**Change the admin password before the site is reachable.**

```bash
php artisan tinker --execute="
\$u = App\Models\User::first();
\$u->password = Hash::make('a-real-password');
\$u->save();
"
```

---

## 9. Handy commands

| Task | Command |
|---|---|
| Fresh database | `php artisan migrate:fresh --seed` |
| Clear caches after editing config | `php artisan config:clear` |
| Watch the log | `tail -f storage/logs/laravel.log` |
| See all routes | `php artisan route:list --except-vendor` |
