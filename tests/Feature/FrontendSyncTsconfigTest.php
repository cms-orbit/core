<?php

declare(strict_types=1);

use CmsOrbit\Core\Frontend\FrontendManifest;
use CmsOrbit\Core\Frontend\FrontendSync;

/**
 * Vite 는 페이지 브리지를 resolve.alias 로 찾지만 tsc 는 그 매핑을 모른다.
 * 그래서 빌드는 통과하는데 types:check 만 브리지마다 TS2307 로 깨졌다.
 * 스타터 킷의 composer ci:check 가 types:check 를 포함하므로 새 프로젝트가
 * 처음부터 빨간 상태였다. 같은 매핑을 tsconfig paths 에 넣어 준다.
 */
function fakeHost(?array $tsconfig = null): string
{
    $base = sys_get_temp_dir().'/orbit-sync-'.bin2hex(random_bytes(6));

    mkdir($base.'/vendor/cms-orbit/core/resources/js/pages/orbit', recursive: true);
    mkdir($base.'/vendor/cms-orbit/core/resources/orbit', recursive: true);
    mkdir($base.'/resources/js/pages', recursive: true);

    file_put_contents(
        $base.'/vendor/cms-orbit/core/resources/orbit/frontend.json',
        json_encode([
            'alias'  => '@cms-orbit/core',
            'jsPath' => 'resources/js',
            'pages'  => [],
        ])
    );

    if ($tsconfig !== null) {
        file_put_contents($base.'/tsconfig.json', is_string($tsconfig['raw'] ?? null)
            ? $tsconfig['raw']
            : json_encode($tsconfig, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    return $base;
}

function hostTsconfigPaths(string $base): array
{
    return json_decode((string) file_get_contents($base.'/tsconfig.json'), true)['compilerOptions']['paths'] ?? [];
}

it('패키지 alias 를 tsconfig paths 에 넣는다', function () {
    $base = fakeHost(['compilerOptions' => ['paths' => ['@/*' => ['./resources/js/*']]]]);

    $result = (new FrontendSync($base))->sync();

    expect($result['tsconfig']['updated'])->toBeTrue()
        ->and(hostTsconfigPaths($base))->toBe([
            '@/*'               => ['./resources/js/*'],
            '@cms-orbit/core/*' => ['./vendor/cms-orbit/core/resources/js/*'],
        ]);
});

it('호스트가 직접 선언한 paths 는 건드리지 않는다', function () {
    $base = fakeHost(['compilerOptions' => ['paths' => ['@/*' => ['./resources/js/*'], '~ui/*' => ['./src/ui/*']]]]);

    (new FrontendSync($base))->sync();

    expect(hostTsconfigPaths($base))->toHaveKey('~ui/*')
        ->and(hostTsconfigPaths($base)['~ui/*'])->toBe(['./src/ui/*']);
});

it('제거된 패키지의 orbit alias 는 걷어낸다', function () {
    $base = fakeHost(['compilerOptions' => ['paths' => [
        '@cms-orbit/core/*' => ['./vendor/cms-orbit/core/resources/js/*'],
        '@cms-orbit/gone/*' => ['./vendor/cms-orbit/gone/resources/js/*'],
    ]]]);

    (new FrontendSync($base))->sync();

    expect(hostTsconfigPaths($base))->not->toHaveKey('@cms-orbit/gone/*')
        ->and(hostTsconfigPaths($base))->toHaveKey('@cms-orbit/core/*');
});

it('paths 가 없는 tsconfig 에도 compilerOptions 를 만들어 넣는다', function () {
    $base = fakeHost(['compilerOptions' => ['strict' => true]]);

    (new FrontendSync($base))->sync();

    $data = json_decode((string) file_get_contents($base.'/tsconfig.json'), true);

    expect($data['compilerOptions']['strict'])->toBeTrue()
        ->and($data['compilerOptions']['paths'])->toBe([
            '@cms-orbit/core/*' => ['./vendor/cms-orbit/core/resources/js/*'],
        ]);
});

it('주석이 있어 안전하게 못 고치는 tsconfig 는 손대지 않고 이유를 알린다', function () {
    $raw = "{\n  // 호스트가 남긴 주석\n  \"compilerOptions\": { \"strict\": true }\n}\n";
    $base = fakeHost(['raw' => $raw]);

    $result = (new FrontendSync($base))->sync();

    expect($result['tsconfig'])->toBe(['updated' => false, 'reason' => 'unparsable'])
        ->and(file_get_contents($base.'/tsconfig.json'))->toBe($raw);
});

it('tsconfig 가 없으면 missing 으로 보고하고 붙여 넣을 블록을 만들어 준다', function () {
    $base = fakeHost();

    $sync = new FrontendSync($base);
    $result = $sync->sync();

    expect($result['tsconfig'])->toBe(['updated' => false, 'reason' => 'missing'])
        ->and($sync->tsconfigPathsBlock(FrontendManifest::discover($base)))
        ->toBe(['@cms-orbit/core/*' => ['./vendor/cms-orbit/core/resources/js/*']]);
});
