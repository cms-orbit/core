# Orbit CMS Development Guidelines

Orbit is a server-driven admin CMS built around declarative Screen/Layout/Field descriptors
and a first-class **Entity** concept. The backend (PHP) describes screens declaratively; the frontend
(Inertia v3 + React 19 + Tailwind v4) renders them from a serialized JSON contract. Follow
these rules when working anywhere in an Orbit-powered application.

## Package independence (critical)

These packages (`cms-orbit/core`, `cms-orbit/announcement`, `cms-orbit/popup`,
`cms-orbit/saas`, `cms-orbit/blog`) must remain **installable in a vanilla Laravel app**
without copying host-project code into the package repository.

- Never commit host-app files (`app/`, `resources/js/pages` bridges, `.env`, project tests)
  into a package PR.
- Never hardcode host domains, tenant names, or demo data that only exist in one project.
- Prefer auto-discovery (service providers, migrations, routes, `resources/orbit/frontend.json`)
  over manual host edits. When host changes are unavoidable, document them in the package
  README under **Host setup** and provide an Artisan helper (`orbit:install`, `orbit:frontend-sync`).
- Satellite packages depend on `cms-orbit/core` via Composer only; do not reach into sibling
  packages through relative filesystem paths.

## Architecture

- **Server-driven UI.** PHP `Screen`, `Layout`, and `Field` objects are serialized to JSON
  and rendered by React. Never hardcode admin markup in React when a PHP layout/field can
  express it. The serialization contract is documented in the core package's `CONTRACT.md`.
- **Contract sync is mandatory.** Any change to how a PHP `Layout`/`Field` serializes MUST be
  mirrored in the React registry (`resources/js/layouts.tsx`, `resources/js/fields.tsx`) and
  the `CONTRACT.md` / `contract.ts` files. Add new layouts/fields to both sides.
- **Entities describe models for the admin.** An `Entity` is an admin descriptor (CRUD,
  permissions, menu, SEO, fields, columns) — it is NOT an Eloquent model. Create entities in
  the host app's `entities/` directory or with `php artisan orbit:make:entity`.

## Entities

- Define admin behaviour by extending the base `Entity` class: `fields()`, `columns()`,
  `filters()`, `label()`, `section()`, `permission()`, `menu()`.
- Register entities via `Orbit::registerEntities(base_path('entities'))` inside
  `App\Orbit\OrbitProvider` (do **not** scatter registrations in `AppServiceProvider`),
  or register from a package service provider with `EntityRegistry::registerClass()`.
- Host `entities/` needs **no** `"Entities\\": "entities/"` entry in the host
  `composer.json` — Core registers a runtime PSR-4 prefix.
- Choose `Entity` for dedicated model tables; choose `DocumentEntity` for shared
  `documents` table content (announcements, popups, banners).
- Prefer entity CRUD screens over bespoke screens unless you need custom flows.
- Permissions are grouped per entity through `ItemPermission`. The role/user editors render
  them as a grouped checkbox matrix (`permission-matrix` field) — keep permission groups
  intact; do not flatten them into a single list.
- Super-admin role permissions auto-sync when the registered permission fingerprint
  changes (`orbit.permissions.auto_sync_super_admin`). Manual: `orbit:fresh-super-admin-role`.

## Internationalization (required from day one)

Orbit treats **Korean (`ko`) as the primary admin locale** with English fallback. Every new
screen, field, menu label, validation message, and React component must be i18n-ready before
merge.

- **Locale config** lives in `config/orbit.php` under `locale` (default `ko`, fallback `en`,
  `supported`, `content`) and is overridable in the admin Localization settings.
- **PHP strings** must go through `__()` so they resolve against `resources/lang/{locale}.json`.
  Applies to entity labels, sections, menu labels, permission descriptions, Blade views, and
  validation copy. Add the English key and the Korean value to the package `ko.json`.
- **React strings** must use the `useT()` hook (from `resources/js/lib/i18n.ts`). Never
  hardcode user-facing copy in components; pass the English key to `t()` and add the Korean
  value to `ko.json`. This includes `aria-label`, button text, empty states, and placeholders.
- **Translatable content fields** use `LocaleTabs` (PHP) / `locale-tabs` (React) to render a
  tab per content locale. Field names are `lang`-scoped (e.g. `ko[title]`).
- **Blade public views** must use `lang="{{ str_replace('_', '-', app()->getLocale()) }}"`
  and `{{ __('Key') }}` for titles and visible text.
- The admin locale resolves from: authenticated user's `locale` column → session → config
  default, applied by the `SetOrbitLocale` middleware.
- When adding a satellite package, call `Locale::registerPath(__DIR__.'/../resources/lang')`
  in the service provider so JSON translations merge with Core.

## Host setup expectations

After `composer require cms-orbit/core`, run:

```bash
php artisan orbit:install
# optional satellites: --with=announcement,popup,sendgo
# CI: --no-interaction --skip-npm  (then npm ci && npm run build separately)
php artisan orbit:frontend-sync   # when adding/removing cms-orbit/* packages later
php artisan boost:install           # optional; merges resources/boost guidelines & skills
```

`orbit:install` publishes config/migrations/stubs, prepares `entities/` and `OrbitProvider`,
runs `orbit:frontend-sync` to generate Inertia page bridges plus Vite aliases from each
package's `resources/orbit/frontend.json`, and can optionally `composer require`
announcement / popup / sendgo (saas/blog are excluded — they need tenancy setup).

## Theming

- **Branding is common** (name/logo/symbol/favicon/theme mode); **colours are per layout mode** in Admin Design. Layout
  modes: `sidebar-split` (default), `sidebar-single`, `topbar`, `hybrid`.
- Colour tokens are exposed as CSS variables (`--color-orbit-primary`, plus an 11-step OKLCH
  shade scale `--color-orbit-primary-50 … -950`, and the same for `secondary`/`accent`).
  Use Tailwind utilities like `bg-orbit-primary-600`, `text-orbit-primary-500`.
- The active layout + resolved colours arrive via the `orbit.brand` shared Inertia prop and
  are injected on the shell root by `useBrandTheme`. Do not read theme colours any other way.
- Layout views live in `resources/js/shell/layouts/`; shared shell parts in
  `resources/js/shell/parts.tsx`. Add new modes to the dispatcher in `dashboard-layout.tsx`.

## UI primitives

Reuse the primitives in `resources/js/ui/` before writing new markup: `Button`, `Badge`,
`Card`/`Section`, `EmptyState`, `SkeletonRows`, `Table`. They follow Filament's `fi-*`
conventions and respect the theme tokens and dark mode.

## Conventions & tooling

- **PHP:** curly braces always, constructor property promotion, explicit return types and
  param hints, PHPDoc over inline comments, `TitleCase` enum keys. Run
  `vendor/bin/pint --dirty --format agent` after editing PHP.
- **Artisan:** use `make:` generators with `--no-interaction`. Inspect routes with
  `php artisan route:list`.
- **Frontend:** run `npm run types:check` after TS changes and `npm run build` (or ask the
  user to run `npm run dev`) so Vite picks up changes.
- **Tests:** every change needs a Pest test. Run `php artisan test --compact --filter=...`.
  Use factories and existing states. Do not delete tests without approval.
- **Deferred props** (Inertia v3): show a pulsing skeleton empty state while data resolves.
- **Laravel Boost:** ship AI context under `resources/boost/guidelines/` and
  `resources/boost/skills/{name}/SKILL.md`. Host apps merge them via `php artisan boost:install`
  (first time) or automatically through `orbit:install` / `orbit:sync` → `boost:update` when Boost
  is already configured.
