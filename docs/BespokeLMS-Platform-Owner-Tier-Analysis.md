# BespokeLMS — Introducing the Platform-Owner Admin Tier

**Prepared:** 22 July 2026
**Scope:** How to introduce a BespokeLMS admin tier that is exclusive to the platform owners (the `bespokelms_owner` role — Marcus Reed, `kemp.house+bespokelms@googlemail.com`), and how the **Admin** area should mean *different things* to that owner versus a tenant owner.
**Status:** Analysis and recommendation only. Nothing in here has been applied to the database or the app. All SQL/PHP below is a proposal to review against the project rules first.

---

## 1. Headline

The owner tier is **half-built already, and the missing half is smaller than it looks** — but two of the gaps are security holes that should be closed regardless of what we decide about menus.

- The `bespokelms_owner` role exists in the `app_role` enum, Marcus's account is live on the **platform** organisation, and the Laravel `SupabaseUser` object already carries `isPlatformOwner()` and `organizationType`. So the *identity* of the tier exists.
- What does **not** exist is the *authority* and the *experience*: there is no server-side authorisation layer (no role middleware, no gates, no owner-only routes), the dashboard forces **every** signed-in user onto the mock "admin" identity, and the navigation is still the frozen prototype's client-side mock rather than role-rendered.
- Under the hood, access control is driven entirely by one idea — **`org_and_descendants(auth_org_id())`**, i.e. "my organisation and everything beneath it in the tree." Because the owner sits at the **root** of the org tree, that single rule already gives the owner platform-wide reach *for the tables that use it correctly*. The problem is that two of the most important tables (`organizations`, `profiles`) **don't** use it — so today the owner can't even list the tenants or the people in them.

The recommended model, answering your steer directly, is an **altitude-aware Admin**: one Admin slot in the navigation whose **label and contents are resolved from the signed-in role**. For the owner it reads *Platform* and opens the cross-tenant console; for a tenant owner it reads *Admin* and opens their own tenant console. The owner additionally gets a **"view as tenant" scope switcher** to descend into any single tenant and administer it in that tenant's context.

---

## 2. What already exists (verified against the live project)

**Database** — project `pqmdtqsscyltykgcwwus` (`BespokeLMS`, eu-west-1), 17 tables, RLS enabled on all of them.

The role ladder is a Postgres enum, `app_role`, with the owner already at the top:

```
bespokelms_owner  →  lms_operator_admin  →  client_admin  →  team_manager  →  learner
```

Access is enforced by seven `SECURITY DEFINER` helper functions and a set of RLS policies built on them:

- `auth_role()` / `auth_org_id()` / `my_profile_id()` / `my_team_id()` — read the caller's own profile.
- `org_and_descendants(root)` — recursive walk down the `organizations.parent_id` tree.
- `visible_profile_ids()` — the set of people the caller may see (self; own team for a team manager; whole subtree for `client_admin` / `lms_operator_admin` / `bespokelms_owner`).
- `is_admin()` — true for `bespokelms_owner`, `lms_operator_admin`, **and** `client_admin`.

The org tree makes the owner special **for free**: the `platform` org is the root, and every operator (Turner Price, TeachHQ, March Foods, FoodComplianceHQ), every client (All Saints, St Mary's, Demo Academy) and every team hangs beneath it. So for any table scoped by `org_and_descendants(auth_org_id())`, the owner's "subtree" is the entire estate, an operator admin's subtree is their own tenant, and a reseller's subtree includes its clients. This is a genuinely elegant base and it should be **kept**.

**Laravel app** (`C:\Claude\bespokelms-app`, Laravel 13, Supabase session auth):

- `App\Auth\SupabaseUser` already exposes `isPlatformOwner(): bool` (returns `role === 'bespokelms_owner'`), `organizationType` (`platform` / `operator` / `client`), and a `roleLabel()` that maps `bespokelms_owner → "Platform Owner"`.
- Identity is a session snapshot rehydrated per request; the profile (role + embedded organisation) is read from `profiles` via PostgREST **with the user's own token**, so RLS applies to the app exactly as it does to any client. Good — it means the RLS fixes below benefit the Laravel app automatically.

---

## 3. The gap register

Five gaps stand between "the owner can log in" and "the owner has a real, safe, exclusive tier." Two are **security** issues that exist *today* and should be fixed on their own merits.

| # | Layer | Gap | Severity | Effect |
|---|-------|-----|----------|--------|
| A | DB (RLS) | `organizations` SELECT policy is **self-only** (`id IN (my own org)`), not subtree | High (blocking) | The owner can read **only the `platform` row** — cannot list tenants. Breaks the entire premise of a tenant-management tier. A reseller likewise can't see its client orgs. |
| B | DB (RLS) | **No INSERT/UPDATE/DELETE policy** on `organizations` at all | High (blocking) | Nobody can create or edit a tenant through the app. The owner's core job (provision/brand/configure tenants) is impossible under RLS. |
| C | DB (RLS) | `profiles` SELECT policy is **self-only** (`auth_user_id = auth.uid()`), ignoring `visible_profile_ids()` | High (blocking) | Admins and the owner see **zero other people**. No rosters, no cross-tenant user view. The helper meant to express this (`visible_profile_ids()`) is applied to enrollments/certificates but not to `profiles` itself. |
| D | DB (RLS) | **Privilege escalation:** platform-global rows (`organization_id IS NULL`) in `platform_settings` and `ai_integrations` are writable by **any** admin, because `is_admin()` includes `client_admin` and `lms_operator_admin`. And the `WITH CHECK` on these policies is a bare `is_admin()` with no subtree constraint. | **Security** | A tenant or client admin can read/write **platform-global settings and the platform's AI integration** (including `api_key_cipher`), and can insert rows for **arbitrary** organisations. This should be owner-only for global rows and subtree-constrained for tenant rows. |
| E | DB (grants) | All seven `SECURITY DEFINER` helpers are `EXECUTE`-granted to `PUBLIC` and `anon` | **Security** (hardening) | Unauthenticated callers can run the RBAC oracles. Revoke from `anon`/`PUBLIC`, grant to `authenticated` only. (Already flagged by Supabase advisors.) |

Two further items are design-level rather than bugs:

- **Courses cascade/visibility (F):** `courses` is currently readable by **every** authenticated user (`courses_read` = `auth.uid() IS NOT NULL`), with no write policy. For the white-label model this needs to become: system courses (`owner_org_id IS NULL`) cascade to everyone; tenant-owned courses (`owner_org_id` set) are visible only within that tenant's subtree; and per-tenant *visibility* of system courses is governed by a new `org_course_settings` table (already noted as a future gap). The owner writes any course; a tenant admin writes only their own. This is the "upload all courses and cascade / tenants control visibility" capability.
- **No authorisation layer in Laravel (G):** every route sits in a single `auth` group; there is no role middleware, no `Gate`, and the dashboard controller unconditionally binds the real user onto `IDENTITY.admin` and calls `setIdentity('admin')`. So the app does **not** yet render different experiences by role, and forbidden navigation is still emitted in the DOM.

---

## 4. Recommended design — the altitude-aware Admin

Your instinct is the right one: **the Admin area should not be one fixed thing gated on/off — it should resolve to a different console depending on the administrator's altitude in the tree.** Concretely:

| Administrator | Home org | The "Admin" slot | What it manages |
|---|---|---|---|
| **Platform owner** (`bespokelms_owner`) | `platform` (root) | Labelled **"Platform"** | Every tenant (create/brand/configure operators & clients), the **global system-course catalogue** that cascades, platform-wide settings, the **platform** AI integration, cross-tenant compliance analytics, the BespokeLMS marketing/CMS site, billing, and an audit trail across all tenants. Plus a **tenant switcher** to descend into any one tenant. |
| **Operator/tenant owner** (`lms_operator_admin`) | an `operator` org | Labelled **"Admin"** | Their own users & teams, their own courses, **visibility toggles** for system courses, their brand kit, their tenant CMS pages, their own AI integration, their tenant's compliance. |
| **Client owner** (`client_admin`) | a `client` org | Labelled **"Admin"** | A narrower version of the tenant console, scoped to the client subtree. |

The key point is that this is **one code path, not two products**. The same Admin screens read `organizations`, `profiles`, `courses`, `v_org_compliance`, etc. — and because RLS already scopes every read by `org_and_descendants(auth_org_id())`, the *owner's* Admin naturally shows the whole estate while a *tenant's* Admin shows just their slice. The differentiator is `role` + `organizations.type`, both of which the app already has in hand.

**Two ways to express this in the navigation** (this is the one decision I'd like you to confirm):

- **Model 1 — Polymorphic Admin (recommended, matches your steer).** There is a single Admin navigation slot. Its **label and destination are role-resolved**: owner → "Platform" → `/platform`; tenant → "Admin" → `/admin`. "The admin menu means different things" is literally true — same slot, different label and console. The owner drills into a tenant via the scope switcher, which re-scopes the console (a persistent banner reads *"Administering: BespokeLMS platform ▾ / Turner Price"*).
- **Model 2 — Separate Platform workspace (the earlier prototype decision).** Keep Admin always tenant-scoped for everyone, and add a distinct 4th workspace **"Platform"** shown only to the owner (`Platform · Admin · Team · My`). Cleaner separation, but Admin then means the *same* thing for everyone and the owner has two admin-ish areas.

They are the same architecture underneath — Model 1 is Model 2 with the owner's "Admin" and "Platform" collapsed into one label-morphing slot. I recommend **Model 1** because it's exactly what you described and it keeps the top-level chrome minimal; if you later want the explicit "Platform" label as a permanent 4th item, that's a one-line nav change, not a re-architecture.

Whichever we pick, the **security rule is the same**: the owner-only area is *server-rendered by role and never emitted in the DOM for anyone else*, and its routes return **404** (not 403) to non-owners so the area's existence isn't disclosed.

---

## 5. Concrete changes

### 5.1 Database — a declarative migration (proposal)

Introduce the altitude predicate that's currently missing — a clean `is_platform_owner()` distinct from the broad `is_admin()` — then fix the four RLS gaps and harden the grants.

```sql
-- 003_platform_owner_tier.sql  (declarative, reversible)

-- (1) The altitude gate: owner-only, distinct from is_admin()
create or replace function public.is_platform_owner(uid uuid default auth.uid())
returns boolean
language sql stable security definer set search_path = public
as $$
  select exists (
    select 1 from profiles
    where auth_user_id = uid and role = 'bespokelms_owner'
  );
$$;

-- (Gap E) lock down EXECUTE on all the SECURITY DEFINER helpers
revoke execute on function
  public.is_platform_owner(uuid), public.is_admin(uuid),
  public.auth_role(), public.auth_org_id(),
  public.my_profile_id(), public.my_team_id(),
  public.org_and_descendants(uuid), public.visible_profile_ids()
from public, anon;
grant execute on function
  public.is_platform_owner(uuid), public.is_admin(uuid),
  public.auth_role(), public.auth_org_id(),
  public.my_profile_id(), public.my_team_id(),
  public.org_and_descendants(uuid), public.visible_profile_ids()
to authenticated;

-- (Gap A) organizations: read the subtree, not just self
drop policy if exists org_read on public.organizations;
create policy org_read on public.organizations for select
  using (id in (select org_and_descendants(auth_org_id())));

-- (Gap B) organizations: owner manages any tenant; operator may edit only its own org
create policy org_owner_all on public.organizations for all
  using (is_platform_owner())
  with check (is_platform_owner());

create policy org_self_update on public.organizations for update
  using (id = auth_org_id() and auth_role() = 'lms_operator_admin')
  with check (id = auth_org_id() and auth_role() = 'lms_operator_admin');

-- (Gap C) profiles: admins/owner see everyone in their subtree, not just self
drop policy if exists profiles_read on public.profiles;
create policy profiles_read on public.profiles for select
  using (id in (select visible_profile_ids()));

-- (Gap D) platform_settings: global rows owner-only; tenant rows subtree + write-constrained
drop policy if exists settings_admin on public.platform_settings;
create policy settings_platform on public.platform_settings for all
  using (organization_id is null and is_platform_owner())
  with check (organization_id is null and is_platform_owner());
create policy settings_tenant on public.platform_settings for all
  using (organization_id in (select org_and_descendants(auth_org_id())) and is_admin())
  with check (organization_id in (select org_and_descendants(auth_org_id())) and is_admin());

-- (Gap D) ai_integrations: same shape. NB api_key_cipher must never be client-selectable.
drop policy if exists ai_admin on public.ai_integrations;
create policy ai_platform on public.ai_integrations for all
  using (organization_id is null and is_platform_owner())
  with check (organization_id is null and is_platform_owner());
create policy ai_tenant on public.ai_integrations for all
  using (organization_id in (select org_and_descendants(auth_org_id())) and is_admin())
  with check (organization_id in (select org_and_descendants(auth_org_id())) and is_admin());
```

Notes:

- Multiple `FOR ALL` / `FOR UPDATE` policies are OR-combined (permissive), so `org_owner_all` + `org_self_update` cleanly give "owner can do anything, operator can edit its own org."
- `api_key_cipher` should be kept out of any column list the client can select. The safest pattern is: never `select` that column from the app; do the decrypt/test-connection **server-side in Laravel** (which the deploy already anticipates), and expose only `status` / `last_tested_at` to the browser.
- **Courses (Gap F)** are deliberately *not* in this migration — they belong with the course-uploader work, which you asked to hold. When we get there: `courses_read` becomes `owner_org_id is null OR owner_org_id in (select org_and_descendants(auth_org_id()))`, plus an `org_course_settings(organization_id, course_id, is_enabled, sort)` table for per-tenant visibility, plus owner/operator write policies.

### 5.2 Laravel — the authorisation layer (proposal)

The app already has the predicate on the user object; it just isn't enforced anywhere. Add a middleware and a gate, and give the owner its own route group.

```php
// app/Http/Middleware/EnsurePlatformOwner.php
final class EnsurePlatformOwner
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // 404, not 403: don't disclose that the platform area exists.
        abort_unless($user instanceof SupabaseUser && $user->isPlatformOwner(), 404);

        return $next($request);
    }
}
```

```php
// bootstrap/app.php — register the alias
$middleware->alias(['platform.owner' => \App\Http\Middleware\EnsurePlatformOwner::class]);
```

```php
// app/Providers/AppServiceProvider.php — a gate for Blade/@can and controller checks
Gate::define('administer-platform',
    fn (SupabaseUser $u) => $u->isPlatformOwner());
```

```php
// routes/web.php — the owner-exclusive area
Route::middleware('auth')->group(function (): void {
    Route::get('/', DashboardController::class)->name('dashboard');
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    // Tenant-scoped admin (operator / client owners) — rendered by role
    Route::prefix('admin')->name('admin.')->group(base_path('routes/admin.php'));

    // Platform-exclusive: only bespokelms_owner may even resolve these
    Route::middleware('platform.owner')->prefix('platform')->name('platform.')
        ->group(base_path('routes/platform.php'));   // tenants, catalogue, global settings, view-as-tenant
});
```

Then the navigation is **server-rendered by role** so forbidden items are never sent to the browser:

```blade
{{-- resources/views/partials/nav.blade.php --}}
@php($u = auth()->user())

@if ($u->isPlatformOwner())
    <a href="{{ route('platform.home') }}" aria-current="{{ request()->routeIs('platform.*') ? 'page' : 'false' }}">
        Platform
    </a>
@elseif (in_array($u->role, ['lms_operator_admin', 'client_admin'], true))
    <a href="{{ route('admin.home') }}" aria-current="{{ request()->routeIs('admin.*') ? 'page' : 'false' }}">
        Admin
    </a>
@endif
{{-- Team / My rendered per role as today --}}
```

The current `DashboardController` behaviour — unconditionally `setIdentity('admin')` — should be replaced so the injected payload carries the **real role**, and the front-end renders the workspace set from that role instead of always landing on "admin." This is the one behavioural change to the frozen UI, and it's logic, not styling, so the pixel-frozen look is untouched.

### 5.3 "View as tenant" (the bridge)

From the Platform console the owner picks a tenant and the whole Admin experience re-scopes to it. Because RLS keys off `auth_org_id()` (the owner's *home* org), true impersonation at the DB layer would need either (a) a server-set "acting org" the RLS reads, or (b) the owner simply operating with their natural whole-tree access and the **application** filtering to the selected tenant. Recommended: **(b) for reads** (owner already sees everything; the app just filters the query to the chosen `organization_id` subtree) and an explicit **acting-org guard for writes** (the controller asserts the target row's `organization_id` is within the selected tenant before writing, with every action written to `audit_log` with `actor_id` = the owner). This keeps RLS simple and makes owner actions on a tenant fully attributable.

---

## 6. Security checklist (do these regardless of the menu decision)

1. Close the platform-global escalation (Gap D) — a tenant/client admin must not touch `organization_id IS NULL` settings or the platform AI key.
2. Fix `profiles_read` and `org_read` to use the existing subtree helpers (Gaps A, C) — currently silently capping every admin tier to "self."
3. Revoke `anon`/`PUBLIC` `EXECUTE` on the SECURITY DEFINER helpers (Gap E).
4. Keep `api_key_cipher` server-side only; never select it into the browser.
5. Owner-only routes return **404** to non-owners and are **not emitted** in anyone else's DOM.
6. Enable Supabase leaked-password protection (advisor WARN) before real users onboard.
7. Every owner action taken *as* a tenant is written to `audit_log` (attribution).

---

## 7. Suggested rollout sequence

1. **Migration `003`** — `is_platform_owner()`, the four RLS fixes, grant hardening. Verify with the existing demo accounts (owner sees 8 orgs + all people; an operator admin sees only their tenant; a client admin only their client; a learner only themselves).
2. **Laravel authorisation** — middleware + gate + `platform`/`admin` route groups; switch the dashboard payload to the real role.
3. **Role-rendered nav** — the altitude-aware Admin/Platform slot (Model 1).
4. **Platform console v1** — the first real-data screen: the **tenant list** from `organizations` + `v_org_compliance` (this becomes possible the moment Gap A is fixed).
5. **Tenant console v1** — operator admin managing their own users/teams from `profiles` + `teams`.
6. *(Later, on your word)* course uploader + cascade/visibility (Gap F), brand-kit UI, CMS.

---

## 8. Open decisions for you

1. **Menu model** — Model 1 (polymorphic Admin that reads "Platform" for the owner) vs Model 2 (separate permanent "Platform" workspace). I recommend Model 1.
2. **Do the security fixes now?** Gaps A–E are correctness/security and are independent of the menu choice. I'd suggest applying migration `003` sooner rather than later.
3. **Implement vs plan** — this document is analysis only. Say the word and I can (a) apply the migration via the Supabase connector, and/or (b) write the Laravel middleware/gate/routing into the app.
