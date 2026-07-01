# CMS Orbit — Core

Server-driven admin engine for Laravel: a **Screen / Layout / Field** system rendered through **Inertia + React**, plus an **Entity** descriptor layer for declarative CRUD, permissions, menus and SEO.

## Requirements

- PHP `^8.3`
- Laravel `^11.0 || ^12.0 || ^13.0`
- Inertia Laravel `^3.0`

## Installation

```bash
composer require cms-orbit/core
php artisan orbit:install
```

Publish the brand assets and config as needed:

```bash
php artisan vendor:publish --tag=orbit-assets
php artisan vendor:publish --tag=orbit-config
```

## Concepts

- **Entity** — a model-agnostic admin descriptor (`CmsOrbit\Core\Entities`) that points at a plain Eloquent model and declares its fields, columns, legend, validation, permissions, menu and CRUD surface. The engine (`CmsOrbit\Core\Foundation\Entity`) turns each entity into routes, permissions and generic CRUD screens.
- **Screen / Layout / Field** — PHP builders serialized to a JSON contract and rendered by matching React components; custom React components can be registered as an escape hatch.
- **Document engine** — multilingual, multi-instance content backed by shared `documents` tables (`DocumentModel` / `DocumentEntity`).
- **Config** — hierarchical site configuration with a central registry and the `orbit_config()` helper.

## Demo

Outside production a **Demo** section of example entities is registered automatically to showcase the field/layout APIs. Toggle it with `ORBIT_DEMO` (or `orbit.demo.enabled`).

## Testing

```bash
composer test
```

## License

MIT
