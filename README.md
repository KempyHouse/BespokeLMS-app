# BespokeLMS — application (Laravel 13 + Supabase)

Production sign-in and app gate for **BespokeLMS**, the multi-tenant white-label LMS.
This is the greenfield Laravel application (the single-file HTML dashboard is a
visual/UX reference only). It ships the first slice of the real stack: a
server-rendered, accessible **sign-in** backed by **Supabase Auth**, with every
application route protected so only signed-in users can view the app.

## How authentication works

- **Supabase Auth (GoTrue) is the single identity provider** for web and (later) Flutter.
  The login screen posts to Laravel; Laravel verifies the email + password against
  Supabase, then establishes a normal **server-side Laravel session**. No password
  is ever duplicated into a local table — there is intentionally no Eloquent `users` table.
- After a successful sign-in the user's **real profile** (name, role, organisation) is
  read from `public.profiles` via PostgREST **using the user's own access token**, so
  Row Level Security applies. That identity snapshot is held server-side in the session.
- Every route lives inside the `auth` middleware group, so unauthenticated visitors are
  redirected to `/login`. Authenticated users hitting `/login` are bounced to the app.
- Password resets are delegated to Supabase (recovery / magic-link email); the
  new-password step is completed in the browser via the bundled Supabase client.

Key code: `app/Support/Supabase` (GoTrue + PostgREST clients), `app/Auth`
(`SupabaseUser`, `SupabaseUserProvider`), `app/Http/Requests/Auth/LoginRequest.php`
(the verify + session establishment), `config/auth.php` + `AppServiceProvider`
(guard wiring), `routes/web.php` (the gate).

## Requirements

- PHP 8.3+ (with `curl`, `mbstring`, `openssl`, `tokenizer`, `xml`)
- Composer 2
- Node 20+ and npm (only if you want to rebuild front-end assets — pre-built assets are included)

## Setup

```bash
composer install
cp .env.example .env          # SUPABASE_URL + publishable key are already filled in
php artisan key:generate
php artisan serve             # http://localhost:8000
```

`.env.example` ships with the real Supabase project URL and the **publishable**
(anon) key, which is safe to expose because it is protected by RLS. The
service-role key is intentionally blank — never commit it.

Front-end assets are pre-built into `public/build`, so the app is styled out of
the box. To rebuild them (or use HMR during development):

```bash
npm install
npm run build     # or: npm run dev
```

## Signing in

The BespokeLMS owner account already exists and is confirmed in Supabase:

- **Marcus Reed** — `kemp.house+bespokelms@googlemail.com` — role `bespokelms_owner`.

A temporary password was set for it (shared separately, not stored in this repo).
Change it after first sign-in via **Forgot password** once Supabase Auth email
delivery (Resend SMTP) is live, or from the Supabase dashboard.

## Testing & formatting

```bash
php artisan test        # PHPUnit feature tests (guest gate, login, logout, reset) — Supabase is faked, no network
vendor/bin/pint         # PSR-12 / Laravel formatting
```

Static analysis is ready but opt-in (avoids pinning a dev dependency to a specific
Laravel minor): `composer require --dev larastan/larastan` then `vendor/bin/phpstan analyse`
(config in `phpstan.neon`).

## Production notes

- Set `APP_ENV=production`, `APP_DEBUG=false`, `APP_URL=https://app.bespokelms.com`.
- Set `SESSION_SECURE_COOKIE=true` (HTTPS). Sessions are encrypted (`SESSION_ENCRYPT=true`)
  because they hold Supabase tokens server-side.
- Login is rate-limited (5 attempts per email+IP); the reset endpoint is throttled.
- In the Supabase dashboard, add `https://app.bespokelms.com/reset-password` to the
  Auth **Redirect URLs** allow-list so recovery links land correctly, and configure
  custom SMTP (Resend) so reset / magic-link emails actually deliver.
- A PHP host is still to be chosen (Forge/VPS, Fly.io, or Laravel Cloud).

## Design tokens

All styling uses design-system tokens defined in `resources/css/app.css`
(`@theme`) — no raw hex or one-off values in templates. These token **values** are
the interim source of truth; the roadmap is to feed them per-tenant from each
organisation's **brand kit** stored in the database (see
`docs/BespokeLMS-BrandKit-Schema-Proposal.md`) so the same components reskin per
tenant.

## Roadmap (next slices)

1. Tenant resolution by operator subdomain (`spatie/laravel-multitenancy` + org-subtree scope).
2. Database-driven **brand kit** tokens (proposal in `docs/`), then per-tenant theming.
3. Port the frozen dashboard UI into Blade components, wired to Supabase data (RLS).
4. Owner-only **Platform** workspace for cross-tenant management.
