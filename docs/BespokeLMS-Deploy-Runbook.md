# BespokeLMS — production deploy runbook (Laravel app + real login)

Goal: take the login-gated dashboard live at **https://app.bespokelms.com**, with the
real Supabase multi-user sign-in.

## Architecture in production

- **The Laravel app** (this repo) runs on a **PHP host** and serves everything behind
  the sign-in — this is what `app.bespokelms.com` points to.
- **Supabase** stays the identity provider + database (unchanged).
- **Netlify** keeps only the marketing site (`bespokelms.com` / `www`). It is *not*
  involved in the app any more — a static host cannot run the login.

Recommended host: **Laravel Cloud** (cloud.laravel.com) — first-party, connects a
GitHub repo, builds and runs Laravel with no server admin. (Alternative: **Laravel
Forge** + a VPS if you want your own server — brief notes at the end.)

What needs *your* accounts (I can't do these from here): GitHub push, the host account,
Namecheap DNS, and the Supabase dashboard toggles.

---

## Step 1 — Put the app in its own GitHub repo

The Laravel app is a **new, separate** repo (keep it apart from the prototype repo,
which Netlify still builds).

```bash
# in the unzipped bespokelms-app folder
git init
git add .
git commit -m "BespokeLMS app: Supabase-backed sign-in + gated dashboard"
# create an empty repo on GitHub first, e.g. KempyHouse/bespokelms-app, then:
git remote add origin https://github.com/KempyHouse/bespokelms-app.git
git branch -M main
git push -u origin main
```

`.gitignore` already excludes `.env`, `/vendor`, `/node_modules`, so no secrets or
build junk are committed.

## Step 2 — Create the host project

On **Laravel Cloud**: New Project → connect GitHub → pick `bespokelms-app` → branch
`main`. It auto-detects Laravel and will run `composer install` + `npm ci && npm run build`
on deploy. Choose a region close to your users (EU) to sit near the Supabase project
(eu-west-1).

## Step 3 — Environment variables

Set these in the host's environment settings:

```
APP_NAME=BespokeLMS
APP_ENV=production
APP_KEY=            # generate: run `php artisan key:generate --show` locally and paste, or let the host generate
APP_DEBUG=false
APP_URL=https://app.bespokelms.com

SUPABASE_URL=https://pqmdtqsscyltykgcwwus.supabase.co
SUPABASE_ANON_KEY=sb_publishable_mqTB-BXLDgPvmF5NgJ_AEQ_8oteU2Rq
SUPABASE_SERVICE_ROLE_KEY=          # leave blank
SUPABASE_REDIRECT_URL=https://app.bespokelms.com/reset-password
SUPABASE_HTTP_TIMEOUT=10

SESSION_DRIVER=database
SESSION_ENCRYPT=true
SESSION_SECURE_COOKIE=true
SESSION_SAME_SITE=lax

CACHE_STORE=database
QUEUE_CONNECTION=database
```

The `SUPABASE_ANON_KEY` is the publishable key — safe to expose (RLS-protected). Never
set the service-role key here unless a future feature needs it.

## Step 4 — Database (for shared sessions)

A scaled/ephemeral host must not use file sessions (they aren't shared across instances).
Provision a **Postgres** database (Laravel Cloud offers a managed one — one click) and set
its connection env (`DB_CONNECTION=pgsql`, host/port/db/user/password as the host provides).
Then the deploy runs migrations, creating the `sessions`, `cache`, and `jobs` tables:

```
php artisan migrate --force
```

(Laravel Cloud can run this automatically on each deploy — enable "run migrations".)
This database is only for Laravel's operational tables; identity + app data stay in Supabase.

## Step 5 — Point app.bespokelms.com at the host

1. In the host, add the custom domain `app.bespokelms.com`. It will show you a **CNAME
   target** (something like `xxxx.laravel.cloud`).
2. In **Namecheap → bespokelms.com → Advanced DNS**, change the `app` record:
   - **Type** CNAME · **Host** `app` · **Value** = the host's CNAME target (replacing the
     current `bespokelms-app.netlify.app`).
3. Wait for DNS + automatic SSL to provision, then the host serves `app.bespokelms.com`.
4. In **Netlify**, remove `app.bespokelms.com` from the `bespokelms-app` project's domains
   (so the app subdomain is no longer claimed there). Netlify keeps `bespokelms.com` / `www`.

## Step 6 — Supabase Auth configuration (dashboard)

In the Supabase dashboard → **Authentication → URL Configuration**:

- **Site URL:** `https://app.bespokelms.com`
- **Redirect URLs — add:** `https://app.bespokelms.com/reset-password`

And configure **custom SMTP** (your Resend BespokeLMS domain) under Auth → Emails, so
password-reset / magic-link emails actually deliver (the built-in Supabase sender is
rate-limited and unreliable). This is the piece that makes the in-app "Forgot password"
work end-to-end.

## Step 7 — Deploy & verify

Trigger a deploy (push to `main`). Then check:

1. Visit `https://app.bespokelms.com` while logged out → you're redirected to `/login`. ✔ gate
2. Sign in as the owner (`kemp.house+bespokelms@googlemail.com`) → you land on the dashboard. ✔
3. The profile menu shows your real identity; **Log out** returns you to `/login`. ✔
4. Confirm `APP_DEBUG=false` and the padlock (HTTPS) is present.

---

## Alternative: Laravel Forge + VPS

If you prefer your own server: create a VPS (DigitalOcean/Hetzner), connect it in Forge,
create a site for `app.bespokelms.com` from the GitHub repo, set the same env vars, add a
managed database, enable Let's Encrypt SSL, and set the deploy script to run
`composer install --no-dev`, `npm ci && npm run build`, `php artisan migrate --force`, and
`php artisan config:cache`. DNS + Supabase steps are identical.
