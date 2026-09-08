<?php

declare(strict_types=1);

use CmsOrbit\Core\Foundation\Http\Middleware\ShareOrbitInertia;
use Illuminate\Support\Facades\Exceptions;

/**
 * safe() 는 마이그레이션 전에도 패널이 뜨도록 기본값으로 되돌린다. 그 폴백
 * 자체는 유지해야 하지만, 조용히 삼키면 원인과 증상이 분리된다 — PostgreSQL
 * 에서는 삼켜진 질의 오류 하나가 트랜잭션을 중단시켜 이후 모든 질의가 25P02
 * 로 죽고 스택 트레이스는 엉뚱한 곳을 가리킨다. 폴백은 유지하고 보고는 하도록
 * 고정한다.
 */
function callSafe(Closure $callback, mixed $default): mixed
{
    $method = new ReflectionMethod(ShareOrbitInertia::class, 'safe');

    return $method->invoke(new ShareOrbitInertia, $callback, $default);
}

it('실패한 클로저의 기본값을 돌려주어 패널이 계속 뜬다', function () {
    Exceptions::fake();

    $result = callSafe(fn () => throw new RuntimeException('brand lookup exploded'), ['fallback']);

    expect($result)->toBe(['fallback']);
});

it('삼킨 예외를 예외 핸들러로 보고한다', function () {
    Exceptions::fake();

    callSafe(fn () => throw new RuntimeException('brand lookup exploded'), null);

    Exceptions::assertReported(
        fn (RuntimeException $e): bool => $e->getMessage() === 'brand lookup exploded'
    );
});

it('성공한 클로저의 값은 그대로 통과시키고 아무것도 보고하지 않는다', function () {
    Exceptions::fake();

    expect(callSafe(fn () => 'menu', []))->toBe('menu');

    Exceptions::assertNothingReported();
});
