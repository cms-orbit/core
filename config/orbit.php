<?php

use App\Http\Middleware\RequirePasswordChange;
use CmsOrbit\Core\Attachment\Engines\Generator;

return [

    /*
    |--------------------------------------------------------------------------
    | Admin Access Mode
    |--------------------------------------------------------------------------
    |
    | Controls how the Orbit admin panel is reached. Three strategies are
    | supported and resolved by the RouteServiceProvider:
    |
    |   - "subdomain": serve the panel from a subdomain derived from APP_URL,
    |                  e.g. "orbit.{appDomain}". The subdomain label is taken
    |                  from "access.subdomain" (default "orbit").
    |   - "domain":    serve the panel from a dedicated domain set explicitly
    |                  in "access.domain".
    |   - "path":      serve the panel under a path prefix on the current
    |                  domain, taken from "access.prefix" (default "settings").
    |
    */

    'access' => [

        'mode' => env('ORBIT_ACCESS_MODE', 'subdomain'),

        // subdomain mode: label prepended to the APP_URL host (orbit.example.com).
        'subdomain' => env('ORBIT_SUBDOMAIN', 'orbit'),

        // domain mode: fully-qualified dedicated domain (admin.example.com).
        'domain' => env('ORBIT_DOMAIN'),

        // path mode: URL prefix (defaults to /settings).
        'prefix' => env('ORBIT_PREFIX', 'settings'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | These middleware groups are assigned to every route in the admin panel.
    | "private" guards authenticated dashboard routes; "public" is used for the
    | authentication screens.
    |
    */

    'middleware' => [
        'public'  => ['web'],
        'private' => ['web', RequirePasswordChange::class, 'orbit'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Guard
    |--------------------------------------------------------------------------
    */

    'guard' => env('AUTH_GUARD', 'web'),

    /*
    |--------------------------------------------------------------------------
    | Localization
    |--------------------------------------------------------------------------
    |
    | Controls the admin panel interface language and the content locales that
    | translatable fields expose. "default" is the initial admin UI language
    | (Korean out of the box); "fallback" is used when a key is missing.
    | "supported" lists the locales offered in the language switcher, while
    | "content" lists the locales used for translatable content fields. Stored
    | Localization settings (the "Localization" config group) override these.
    |
    */

    'locale' => [
        'default'   => env('ORBIT_LOCALE', 'ko'),
        'fallback'  => env('ORBIT_FALLBACK_LOCALE', 'en'),
        'supported' => ['ko', 'en'],
        'content'   => ['ko', 'en'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication Pages
    |--------------------------------------------------------------------------
    */

    'auth' => true,

    /*
    |--------------------------------------------------------------------------
    | Main / Profile Routes
    |--------------------------------------------------------------------------
    */

    'index'   => 'orbit.main',
    'profile' => 'orbit.profile',

    /*
    |--------------------------------------------------------------------------
    | Dashboard Resources (extra stylesheets / scripts)
    |--------------------------------------------------------------------------
    */

    'resource' => [
        'stylesheets' => [],
        'scripts'     => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Vite Resource
    |--------------------------------------------------------------------------
    */

    'vite' => [],

    /*
    |--------------------------------------------------------------------------
    | Template View (brand header / footer fragments)
    |--------------------------------------------------------------------------
    */

    'template' => [
        'header' => '',
        'footer' => '',
    ],

    /*
    |--------------------------------------------------------------------------
    | Default Attachment Configuration
    |--------------------------------------------------------------------------
    */

    'attachment' => [
        'disk'      => env('ORBIT_FILESYSTEM_DISK', 'public'),
        'generator' => Generator::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Notifications
    |--------------------------------------------------------------------------
    */

    'notifications' => [
        'enabled'  => true,
        'interval' => 60,
    ],

    /*
    |--------------------------------------------------------------------------
    | Demo / Examples
    |--------------------------------------------------------------------------
    |
    | When enabled, Orbit registers a "Demo" section of example entities that
    | showcase the Entity/Screen/Field APIs (grouped fields, field types,
    | rendered legends). It is enabled automatically outside production so a
    | fresh install has something to explore; set ORBIT_DEMO to force it on/off.
    |
    */

    'demo' => [
        'enabled' => env('ORBIT_DEMO') !== null
            ? filter_var(env('ORBIT_DEMO'), FILTER_VALIDATE_BOOLEAN)
            : env('APP_ENV', 'production') !== 'production',
        'section' => 'Demo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Search (searchable models)
    |--------------------------------------------------------------------------
    */

    'search' => [
        // \App\Models\User::class
    ],

    /*
    |--------------------------------------------------------------------------
    | Fallback Page
    |--------------------------------------------------------------------------
    */

    'fallback' => true,

    /*
    |--------------------------------------------------------------------------
    | Prevents Abandonment
    |--------------------------------------------------------------------------
    */

    'prevents_abandonment' => true,

    /*
    |--------------------------------------------------------------------------
    | Analytics
    |--------------------------------------------------------------------------
    |
    | Country codes are resolved from trusted edge headers by default. When no
    | edge header is present, enable MaxMind GeoIP with ORBIT_ANALYTICS_GEOIP_*.
    | On local development (Valet, localhost) set ORBIT_ANALYTICS_DEV_COUNTRY
    | to simulate a country for new pageviews.
    |
    | In production, place the app behind Cloudflare, CloudFront, App Engine,
    | Fly.io, or Vercel so the corresponding country header is present, or use
    | a GeoLite2-Country.mmdb database for self-hosted deployments.
    |
    */

    'analytics' => [
        'country_headers' => array_values(array_filter(array_map(
            static fn (string $header): string => trim($header),
            explode(',', (string) env('ORBIT_ANALYTICS_COUNTRY_HEADERS', '')),
        ))),
        'dev_country_code' => env('ORBIT_ANALYTICS_DEV_COUNTRY'),
        'geoip'            => [
            'enabled'       => (bool) env('ORBIT_ANALYTICS_GEOIP_ENABLED', false),
            'database_path' => env(
                'ORBIT_ANALYTICS_GEOIP_DATABASE_PATH',
                storage_path('app/geoip/GeoLite2-Country.mmdb'),
            ),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Application Service Provider
    |--------------------------------------------------------------------------
    |
    | The host application's Orbit provider. Registered only when the class
    | exists, so a fresh install without it boots cleanly.
    |
    */

    'provider' => 'App\Orbit\OrbitProvider',

];
