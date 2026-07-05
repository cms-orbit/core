---
name: orbit-i18n
description: Implement multilingual UI and content in Orbit packages and host apps. Activate when adding labels, React copy, Blade text, LocaleTabs, translation files, or fixing hardcoded Korean/English strings in Orbit admin or public pages.
---

# Orbit internationalization

## When to use

- Any new screen, field, menu, button, empty state, or validation message
- Public Inertia/Blade pages in satellite packages (announcement, popup, blog)
- Adding a new supported locale

## Rules

| Layer | Rule |
| --- | --- |
| PHP / Blade | `__('English key')` + entry in package `resources/lang/ko.json` |
| React / TSX | `const t = useT();` then `t('English key')` — never hardcode UI copy |
| Content fields | `LocaleTabs` (PHP) / `locale-tabs` (React) with per-locale names like `ko[title]` |
| HTML lang | `lang="{{ str_replace('_', '-', app()->getLocale()) }}"` in Blade roots |
| Package boot | `Locale::registerPath(__DIR__.'/../resources/lang')` in the service provider |

## Checklist before merge

1. Grep for hardcoded Korean/English UI strings in changed TSX/PHP/Blade files.
2. Add missing keys to **every** affected package's `ko.json` (Core + satellite if touched).
3. Use English as the translation **key**; store Korean as the value in `ko.json`.
4. Update tests to assert `__('...')` rather than literal strings when locale is `ko`.
5. For `aria-label`, placeholders, and toast text — translate them too.

## Common mistakes

- Hardcoding Korean in TSX because the default locale is Korean — still use `t()`.
- Adding keys only to Core when the string lives in a satellite package.
- Forgetting Blade `<title>` and `<html lang>` on public container views.
