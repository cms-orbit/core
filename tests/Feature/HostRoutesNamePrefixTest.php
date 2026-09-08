<?php

declare(strict_types=1);

use CmsOrbit\Core\Foundation\Providers\RouteServiceProvider;
use Illuminate\Support\Facades\Route;

/**
 * 패키지 자체 라우트 파일은 "orbit." 이름 접두사를 받지만 호스트의
 * routes/orbit.php 는 받지 않았다. 무조건 붙이면 이미 직접 접두사를 적어둔
 * 호스트의 라우트 이름이 "orbit.orbit.*" 이 되므로 opt-in 으로 뒀다.
 * 기본값이 꺼짐인 것과, 켰을 때 실제로 붙는 것을 함께 고정한다.
 */
function mapHostRoutes(bool $namePrefix): void
{
    file_put_contents(
        base_path('routes/orbit.php'),
        "<?php\n\nuse Illuminate\\Support\\Facades\\Route;\n\n"
        ."Route::get('host-probe', fn () => 'ok')->name('host-probe');\n"
    );

    config(['orbit.host_routes.name_prefix' => $namePrefix]);

    app(RouteServiceProvider::class, ['app' => app()])->map();

    // 런타임에 추가한 라우트는 이름 조회 캐시에 반영해야 Route::has() 가 본다.
    Route::getRoutes()->refreshNameLookups();
}

afterEach(function () {
    $path = base_path('routes/orbit.php');

    if (file_exists($path)) {
        unlink($path);
    }
});

it('기본값에서는 호스트 라우트에 orbit. 접두사를 붙이지 않는다', function () {
    mapHostRoutes(namePrefix: false);

    expect(Route::has('host-probe'))->toBeTrue()
        ->and(Route::has('orbit.host-probe'))->toBeFalse();
});

it('켜면 호스트 라우트도 패키지 파일과 같은 orbit. 접두사를 받는다', function () {
    mapHostRoutes(namePrefix: true);

    expect(Route::has('orbit.host-probe'))->toBeTrue()
        ->and(Route::has('host-probe'))->toBeFalse();
});
