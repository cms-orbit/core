<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\SocialiteServiceProvider;
use SocialiteProviders\Apple\Provider;

uses(RefreshDatabase::class);

beforeEach(function (): void {
    $this->app->register(SocialiteServiceProvider::class);
    Socialite::shouldReceive('driver')->with('apple')->andReturnUsing(
        fn () => new Provider(app('request'), 'test-client', 'test-secret', 'https://localhost/login/apple/callback'),
    );
});

it('binds Apple authorization to an encrypted secure browser nonce', function (): void {
    $response = $this->get(route('orbit.login.social.redirect', ['provider' => 'apple']));
    $response->assertRedirect();
    parse_str(parse_url($response->headers->get('Location'), PHP_URL_QUERY), $query);
    $cookie = collect($response->headers->getCookies())->first(fn ($cookie): bool => $cookie->getName() === 'socialite_apple_nonce');

    expect($query['response_mode'])->toBe('form_post')
        ->and($query['nonce'])->not->toBeEmpty()
        ->and($cookie)->not->toBeNull()
        ->and($cookie->isSecure())->toBeTrue()
        ->and($cookie->isHttpOnly())->toBeTrue()
        ->and($cookie->getSameSite())->toBe('none');
});

it('rejects Apple POST callbacks without a valid browser nonce', function (?string $nonce): void {
    if ($nonce !== null) {
        $this->withCookie('socialite_apple_nonce', $nonce);
    }

    $this->post(route('orbit.login.social.callback', ['provider' => 'apple']), ['code' => 'untrusted-code'])
        ->assertForbidden();
})->with([null, 'tampered-cookie']);

it('keeps the external callback reachable with request forgery middleware enabled', function (): void {
    $this->app['env'] = 'local';
    $this->post(route('orbit.login.social.callback', ['provider' => 'apple']), ['code' => 'untrusted-code'])
        ->assertForbidden();
});

it('does not accept POST callbacks for other providers', function (): void {
    $this->post(route('orbit.login.social.callback', ['provider' => 'google']))->assertStatus(405);
});
