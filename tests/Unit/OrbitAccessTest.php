<?php

declare(strict_types=1);

use CmsOrbit\Core\Foundation\Routing\OrbitAccess;
use CmsOrbit\Core\Support\Facades\Orbit;

/**
 * OrbitAccess 는 관리자 패널의 마운트 지점(도메인·접두사·미들웨어)을 결정하고,
 * 위성 패키지(cms-orbit/saas)가 서브클래스를 바인딩해 인스턴스별로 바꿔 끼운다.
 * 그 확장점의 기반이 되는 config 기반 기본 동작을 여기서 고정한다.
 */
it('subdomain 모드에서 orbit 서브도메인 호스트를 해석한다', function () {
    config([
        'app.url'                => 'https://example.com',
        'orbit.access.mode'      => 'subdomain',
        'orbit.access.subdomain' => 'orbit',
    ]);

    $access = new OrbitAccess;

    expect($access->domain())->toBe('orbit.example.com')
        ->and($access->prefix())->toBe('')
        ->and($access->url('/dashboard'))->toBe('/dashboard');
});

it('domain 모드에서는 설정된 도메인을 쓰고 접두사를 붙이지 않는다', function () {
    config([
        'orbit.access.mode'   => 'domain',
        'orbit.access.domain' => 'admin.example.com',
    ]);

    $access = new OrbitAccess;

    expect($access->domain())->toBe('admin.example.com')
        ->and($access->prefix())->toBe('');
});

it('path 모드에서는 현재 호스트에 접두사를 붙여 마운트한다', function () {
    config([
        'orbit.access.mode'   => 'path',
        'orbit.access.prefix' => 'settings',
    ]);

    $access = new OrbitAccess;

    expect($access->domain())->toBeNull()
        ->and($access->prefix())->toBe('/settings')
        ->and($access->url('/dashboard'))->toBe('/settings/dashboard')
        ->and($access->url())->toBe('/settings');
});

it('접두사에 붙은 여분의 슬래시를 정규화한다', function () {
    config([
        'orbit.access.mode'   => 'path',
        'orbit.access.prefix' => '/manage/',
    ]);

    expect((new OrbitAccess)->prefix())->toBe('/manage');
});

it('설정된 private·public 미들웨어를 그대로 노출한다', function () {
    config([
        'orbit.middleware.private' => ['web', 'orbit'],
        'orbit.middleware.public'  => ['web'],
    ]);

    $access = new OrbitAccess;

    expect($access->middleware())->toBe(['web', 'orbit'])
        ->and($access->publicMiddleware())->toBe(['web']);
});

it('Orbit::prefix() 가 컨테이너에 바인딩된 리졸버를 통과한다', function () {
    config([
        'orbit.access.mode'   => 'path',
        'orbit.access.prefix' => 'settings',
    ]);

    // 위성 패키지가 이 바인딩을 갈아끼우는 것이 확장 방식이므로,
    // Orbit::prefix() 가 인스턴스를 직접 만들지 않고 컨테이너를 타는지 확인한다.
    app()->instance(OrbitAccess::class, new class extends OrbitAccess
    {
        public function url(string $path = ''): string
        {
            return '/tenant-42'.$path;
        }
    });

    expect(Orbit::prefix('/dashboard'))->toBe('/tenant-42/dashboard');
});
