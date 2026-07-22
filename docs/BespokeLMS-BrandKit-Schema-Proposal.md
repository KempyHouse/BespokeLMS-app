# BespokeLMS — Brand Kit & Design-Token Schema (proposal)

Status: **proposal for review** — not yet applied to the database. Per the project
rules, schema changes are designed and reviewed before migration.

## Goal

Make the brand kit a first-class part of the database schema so that:

1. **BespokeLMS** has its own default brand kit (the platform brand).
2. **Every tenant** — in-house (e.g. March Foods), reseller (e.g. Turner Price), and
   owned-brand (e.g. TeachHQ, FoodComplianceHQ) — can build and manage their own
   brand kit **using the same design-system tokens** (one shared contract, many
   value sets).
3. The app resolves the active tenant's kit at request time and emits CSS custom
   properties, so the same components reskin per tenant (true white-label).

This aligns with the confirmed stack decision (tokens as the white-label mechanism;
W3C DTCG / Style Dictionary as the shared source feeding Tailwind + Dart).

## Shape

Three concepts:

- **Token contract** — the canonical, shared list of token *names* + types every kit
  themes against (e.g. `color.brand.accent`, `radius.control`, `font.sans`). This is
  the single source of truth for *what* can be themed.
- **Brand kit** — a named, versioned set of token *values* owned by one organisation
  (draft/published; one published default per org). Plus brand assets (logo, favicon)
  in Supabase Storage.
- **Resolution** — active tenant → published kit → merge over the platform defaults →
  emit `:root { --color-brand-accent: …; … }`.

### Proposed tables (declarative, snake_case, timestamptz, RLS — illustrative)

```sql
-- WHAT can be themed: the shared design-system contract (platform-managed).
create table public.design_tokens (
  key           text primary key,                 -- e.g. 'color.brand.accent'
  category      text not null,                     -- 'color' | 'dimension' | 'font' | 'shadow' | 'radius'
  css_var       text not null unique,              -- e.g. '--color-brand-accent'
  type          text not null,                     -- DTCG $type: 'color' | 'dimension' | 'fontFamily' | 'shadow'
  default_value jsonb not null,                    -- DTCG $value (platform default)
  description   text,
  sort          int  not null default 0,
  created_at    timestamptz not null default now(),
  updated_at    timestamptz not null default now()
);

-- A tenant's brand kit (values). One published default per organisation.
create table public.brand_kits (
  id              uuid primary key default gen_random_uuid(),
  organization_id uuid not null references public.organizations(id) on delete cascade,
  name            text not null,
  status          text not null default 'draft',   -- 'draft' | 'published'
  is_default      boolean not null default false,
  logo_path       text,                            -- Supabase Storage object path
  favicon_path    text,
  created_at      timestamptz not null default now(),
  updated_at      timestamptz not null default now()
);

-- The overridden token VALUES for a kit (only what differs from the contract default).
create table public.brand_kit_tokens (
  brand_kit_id uuid not null references public.brand_kits(id) on delete cascade,
  token_key    text not null references public.design_tokens(key),
  value        jsonb not null,                     -- DTCG $value
  primary key (brand_kit_id, token_key)
);

-- At most one published default kit per organisation.
create unique index brand_kits_one_default_per_org
  on public.brand_kits(organization_id)
  where is_default and status = 'published';
```

(`organizations.brand_theme jsonb` already exists and can remain as a denormalised
cache of the resolved published kit for fast reads, refreshed on publish.)

### RLS sketch

- `design_tokens`: readable by all authenticated users; writable only by
  `bespokelms_owner` (the shared contract is platform-owned).
- `brand_kits` / `brand_kit_tokens`: a tenant admin can manage kits **for their own
  organisation** (`organization_id = auth_org_id()`); the owner can manage any
  (cascade), reusing the existing `is_admin()` / `auth_org_id()` / org-subtree helpers.

### Resolution at request time

1. Resolve tenant from the subdomain (operator) → active `organization_id`.
2. Load `design_tokens` defaults + the org's published kit overrides (or the cached
   `organizations.brand_theme`).
3. Merge overrides over defaults and emit `:root { --css-var: value; … }` into the
   layout, so the token *names* in `resources/css/app.css` stay constant while the
   *values* change per tenant.
4. The same shared source can generate the Tailwind `@theme` (web) and a Dart theme
   (Flutter) via Style Dictionary.

## Why this shape

- One **shared contract** (`design_tokens`) guarantees every tenant themes against the
  *same* tokens — consistent components, no per-tenant bespoke CSS.
- Storing only **overrides** per kit keeps kits small and lets platform default changes
  flow through automatically.
- Draft/published + one-default-per-org supports safe editing and a brand-kit builder UI.
- Assets in Storage + RLS by organisation keep tenants strictly isolated.

## Not included yet (deliberately)

Per-tenant course visibility, the CMS pages tables, and the brand-kit **builder UI**
are separate follow-ups. This proposal is only the schema + resolution model for the
tokens themselves.
