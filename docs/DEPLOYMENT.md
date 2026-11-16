# SALE7LI — Production Deployment Runbook

This is the operational runbook for deploying **SALE7LI** (Laravel 12 · Blade/Tailwind 4 · Vite · Alpine · React) to a real production environment. It documents this project specifically — not generic Laravel.

Local development is covered by the root `README.md`. This document assumes a genuine server / container deployment behind HTTPS.

---

## 1. Production prerequisites

| Requirement | Notes |
|---|---|
| **PHP 8.2+** | `composer.json` requires `^8.2`; the project is developed and tested on 8.5. Extensions: the Laravel 12 defaults plus `pdo_mysql`. |
| **Composer 2** | For `composer install` on the build host. |
| **Node.js 20+ / npm** | Only needed to build front-end assets (`npm ci && npm run build`). It can run on a build/CI host — Node is **not** required on the runtime host if `public/build/` is deployed as an artifact. |
| **MySQL 8** (or MariaDB 10.6+) | SQLite is development-only. The schema uses foreign keys, `enum` columns, JSON columns and composite indexes. |
| **Web server** | Nginx or Apache with `public/` as the document root, PHP-FPM. |
| **HTTPS** | Mandatory. The app forces the `https` scheme in production and emits HSTS on secure requests. Terminate TLS at the web server or at a load balancer / ingress in front of it (see §5). |
| **Writable paths** | `storage/` and `bootstrap/cache/` must be writable by the PHP-FPM user. User uploads are written under `storage/app/private/`. |
| **Cron** | **Not required.** There are no scheduled tasks (`routes/console.php` defines only a `sale7li:health` command). Do not add `schedule:run`. |
| **Queue worker** | **Not required today.** Nothing in the app is queued — mail (verification, password reset) is sent synchronously. `QUEUE_CONNECTION=database` and the `jobs` table exist for future use; if queued work is ever introduced, run a supervised `php artisan queue:work`. |
| **Timezone** | Fixed to `Asia/Beirut` in `config/app.php` (single-timezone platform; `worker_availability` times are stored without a zone). |

---

## 2. Environment configuration

Create the production `.env` from `.env.example` and set the values below. **Never commit `.env`, `APP_KEY`, the database password, or SMTP credentials.** Provide secrets through the platform's environment/secret store.

### Required

| Variable | Production value |
|---|---|
| `APP_ENV` | `production` |
| `APP_DEBUG` | `false` — the Content-Security-Policy is only emitted when debug is off. Never run production with debug on. |
| `APP_URL` | `https://<your-domain>` — feeds URL generation, the password-reset link, the marketplace API cache, SEO canonicals, and the `LanguageController` same-origin redirect check. |
| `APP_KEY` | Generate once: `php artisan key:generate --show` and store it as a secret. Rotating? put the old key(s) in `APP_PREVIOUS_KEYS` (comma-separated) so existing sessions/cookies decrypt during the cutover. |
| `DB_CONNECTION` | `mysql` |
| `DB_HOST` / `DB_PORT` / `DB_DATABASE` / `DB_USERNAME` / `DB_PASSWORD` | Real MySQL credentials. |
| `SESSION_DRIVER` | `database` (the `sessions` table is created by migration; a `redis` driver is also fine if available). |
| `SESSION_SECURE_COOKIE` | `true` (auto-true when `APP_ENV=production`, but set it explicitly). |
| `SESSION_ENCRYPT` | `true`. |
| `CACHE_STORE` | `database` (the `cache` table is created by migration; `redis` is fine if available). |
| `QUEUE_CONNECTION` | `database` (unused today; leave as-is). |
| `FILESYSTEM_DISK` | `public` or `local`. Note: **all user uploads are pinned to the private `local` disk regardless of this value** — this only affects any future public assets. |
| `MAIL_MAILER` + transport vars | A **real** transport (see §4). `log` is development only. |
| `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME` | Your real sender identity. |
| `LOG_LEVEL` | `warning` (or `error`). |
| `LOG_CHANNEL` | `stack` is fine; the underlying `single` file does not rotate — use OS `logrotate` on `storage/logs/laravel.log`, or point `LOG_CHANNEL` at a rotating channel. |

### Required if behind a reverse proxy / load balancer

| Variable | Value |
|---|---|
| `TRUSTED_PROXIES` | The proxy IP(s) / CIDR(s), comma-separated (e.g. `10.0.0.0/8,192.0.2.10`), or `*` **only** when the app is reachable exclusively through the proxy. See §5. |

### Optional / operational

| Variable | Purpose |
|---|---|
| `SUPPORT_EMAIL` | "Contact support" address. Blank → falls back to the `config/sale7li.php` default. |
| `SUPPORT_WHATSAPP` | Digits-only WhatsApp number for "contact support"; the button hides when blank. |
| `WHISH_ACCOUNT_NAME` / `WHISH_ACCOUNT_NUMBER` / `WHISH_INSTRUCTIONS` | Real WHISH transfer details shown to customers paying manually (see §9). Keep out of source. |
| `STALE_REQUEST_HOURS` | Hours before an unassigned request is flagged "stale" in the admin queue (default `24`). |
| `APP_PREVIOUS_KEYS` | Comma-separated old `APP_KEY`s to accept during key rotation. |

### Never in production

- `APP_DEBUG=true`
- `MAIL_MAILER=log`
- The seeded demo accounts (`admin@sale7li.test`, `customer@sale7li.test`, `worker@sale7li.test`) and their published passwords.

---

## 3. Installation / first deploy

Run on the build host (or the release directory):

```bash
composer install --no-dev --optimize-autoloader
npm ci
npm run build            # writes public/build/ — ship this directory

php artisan key:generate            # only if APP_KEY is not already provisioned
php artisan migrate --force         # --force is required in production
php artisan storage:link            # symlinks public/storage -> storage/app/public
                                    # (public assets only; uploads stay private)

php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan event:cache
```

Point the web server document root at `public/`.

### Health check

`GET /up` returns `200` when the framework boots (configured in `bootstrap/app.php`). Use it for the load balancer / uptime probe. There is also an artisan check: `php artisan sale7li:health`.

---

## 4. Mail transport (required)

**Email verification and password reset both depend on working outbound mail.** With `MAIL_MAILER=log` a real user can register but can never verify their address — and because customer booking / payment / review / complaint actions are gated behind `verified`, an unverified user is effectively locked out. Password reset is equally non-functional.

Mail is sent **synchronously** (there is no queue and notifications are not `ShouldQueue`), so choose a responsive provider — a slow SMTP handshake will slow the affected web request (registration, resend, forgot-password).

### SMTP

```
MAIL_MAILER=smtp
MAIL_HOST=smtp.your-provider.example
MAIL_PORT=587
MAIL_USERNAME=<secret>
MAIL_PASSWORD=<secret>
MAIL_SCHEME=              # blank = auto (STARTTLS on 587, direct TLS on 465); or "smtps"
MAIL_FROM_ADDRESS="no-reply@your-domain.example"
MAIL_FROM_NAME="${APP_NAME}"
```

### Hosted API transport

`ses`, `postmark`, `resend`, and `mailgun` are supported by the framework mail config. Set `MAIL_MAILER` accordingly and supply that provider's credentials (`AWS_*`, `POSTMARK_TOKEN`, `RESEND_KEY`, `MAILGUN_*`) via secrets.

### Verify after deploy

```bash
php artisan tinker
>>> Illuminate\Support\Facades\Mail::raw('SALE7LI mail check', fn($m) => $m->to('you@example.com')->subject('SALE7LI mail check'));
```

Then confirm end-to-end: register a throwaway account and confirm the verification email arrives and the link verifies.

### Not in scope

Do **not** add a queue system or make notifications `ShouldQueue` as part of standing up mail. If the team later wants queued mail, that is a separate change and requires a supervised `queue:work` process.

---

## 5. Reverse proxy, TLS & trusted proxies

SALE7LI forces the `https` URL scheme in production (`AppServiceProvider`) and emits `Strict-Transport-Security` **only when the incoming request is genuinely secure** (`SecurityHeaders` middleware).

When TLS is terminated at a load balancer / ingress and plain HTTP is forwarded to the app, Laravel must be told which proxy to trust before it will honour `X-Forwarded-Proto`, `X-Forwarded-For`, etc. Otherwise `request->secure()` stays `false`, HSTS is not emitted, and client IPs in logs are the proxy's.

- **Default (no proxy, or TLS terminates at the app's own web server):** leave `TRUSTED_PROXIES` empty. Nothing is trusted; forwarded headers are ignored; a spoofed `X-Forwarded-Proto: https` cannot make an HTTP request look secure.
- **Behind a proxy with a known address / range:**
  `TRUSTED_PROXIES=10.0.0.0/8` or `TRUSTED_PROXIES=192.0.2.10,192.0.2.11`
- **Behind a proxy whose address is dynamic AND the app is not reachable except through it** (e.g. a managed PaaS / ELB):
  `TRUSTED_PROXIES=*`
  Only use `*` when direct access to the app port is firewalled off.

This is wired in `bootstrap/app.php` (`$middleware->trustProxies(at: ...)`), reading `TRUSTED_PROXIES` from the environment. The default forwarded-header set (`FOR`, `HOST`, `PORT`, `PROTO`, plus AWS ELB) is used.

**Also ensure the proxy sets `X-Forwarded-Proto: https`** for TLS requests — Laravel derives `request->secure()` from it once the proxy is trusted.

---

## 6. Database, migrations & schema notes

- `php artisan migrate --force` on every deploy that ships new migrations. All 11 current migrations are additive / nullable / reversible.
- The `database` session, cache and queue drivers need the `sessions`, `cache`, `cache_locks`, `jobs`, `job_batches`, `failed_jobs` tables — they are created by the framework-tables migration, so a plain `migrate` covers them.
- Do **not** run `migrate:fresh` against a production database — it drops every table.
- Money columns are `decimal(10,2)`; commission columns are nullable by design.

---

## 7. Storage & uploads

- User uploads (message attachments, booking photos, completion photos, WHISH payment proofs, worker photos) are written to the **private** disk: `storage/app/private/…`, with framework-hashed filenames.
- They are served **only** through authorized routes (`AttachmentController`, `WorkerController@photo`) after a policy check. There is no public URL for them — `storage:link` exposes only `storage/app/public/`.
- Back up `storage/app/private/` alongside the database. If you run multiple app instances, put `storage/app/private/` on shared storage (NFS / object-store driver) so every instance can serve uploads.
- `storage/logs/` and `storage/framework/{cache,sessions,views}` are per-instance and disposable.

---

## 8. Seeding & the admin account

- `php artisan db:seed` (or `migrate --seed`) is **safe to run in production for the reference data only**: it always upserts the 8 service categories (idempotent) and then returns early — `if (app()->environment('production')) return;` guards every demo account and sample row.
- Because the demo accounts are not created in production, **provision the first administrator manually**:

  ```bash
  php artisan tinker
  >>> App\Models\User::create([
  ...   'name' => 'Ops Admin',
  ...   'email' => 'ops@your-domain.example',
  ...   'password' => 'a-long-random-password',   // hashed by the model cast
  ...   'role' => 'admin',
  ...   'status' => 'active',
  ...   'locale' => 'en',
  ... ])->markEmailAsVerified();
  ```

- If a database was ever seeded with demo data in a non-production environment and then promoted, delete/rotate `admin@sale7li.test`, `customer@sale7li.test`, `worker@sale7li.test`.

---

## 9. Payments (manual / WHISH)

SALE7LI has **no payment gateway integration** and stores **no card data**. Two paths exist:

- **"Pay securely"** — an internal mock capture (`App\Payments\PaymentCaptureService`) that marks the payment `paid` immediately. This is a demo/test capture; wiring a real PSP is out of scope for this deployment.
- **WHISH manual transfer** — the customer sees the `WHISH_ACCOUNT_*` details, transfers out of band, submits a reference (+ optional proof), and an **admin verifies** it in `/admin/payments/whish`, which flips the payment to `paid`.

Set `WHISH_ACCOUNT_NAME`, `WHISH_ACCOUNT_NUMBER`, `WHISH_INSTRUCTIONS` in the production environment. Commission is derived server-side from `platform_settings.commission_rate` (seeded at `0.1000`) and is adjustable in `/admin/settings/commission`.

---

## 10. Post-deploy verification checklist

- [ ] `GET /up` → `200`; site loads over HTTPS; `http://` redirects to `https://`.
- [ ] Response carries `Content-Security-Policy`, `X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`, and — over real HTTPS — `Strict-Transport-Security`.
- [ ] Behind a proxy: application logs show real client IPs, not the proxy IP.
- [ ] Register a throwaway account → verification email is **received** → link verifies → dashboard loads.
- [ ] Forgot-password → reset email is received → new password works.
- [ ] Log in as the manually-created admin; `/admin/dashboard` loads.
- [ ] Assets load from `public/build/` (no 404s, no `@vite` dev-server references).
- [ ] `storage/` and `bootstrap/cache/` are writable; an upload (e.g. a worker photo) succeeds and is served only to authorized users.
- [ ] `.env`, `APP_KEY`, DB and SMTP secrets are **not** in the repo or the image.
- [ ] Demo accounts / demo passwords are absent.

---

## 11. Redeploys & rollback

**Redeploy:**

```bash
php artisan down --render="errors::503"        # optional
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache route:cache view:cache event:cache
php artisan up
```

Clear stale caches if config changed but a cache step was skipped: `php artisan optimize:clear`.

**Rollback:** deploy the previous release artifact and re-run the cache steps. Only roll back the database if the release's migrations are known-reversible and there is a fresh backup — prefer forward fixes for schema.

---

## 12. What this deployment does **not** change

Per Phase 31 Step 2 scope, no application behaviour was modified. Payments, analytics, the marketplace query pipeline, `BookingDraft` / availability, the request state machine, and the authentication / email-verification / password-reset flows are unchanged — this step only adds environment-driven trusted-proxy support and production configuration documentation.
