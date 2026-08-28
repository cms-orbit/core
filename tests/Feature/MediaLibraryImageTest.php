<?php

declare(strict_types=1);

use CmsOrbit\Core\Media\MediaLibrary;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Drivers\Gd\Driver;
use Intervention\Image\ImageManager;

uses(RefreshDatabase::class);

/**
 * intervention/image 4.x 로 옮기면서 MediaLibrary 의 이미지 경로가 쓰는 API 가
 * 전부 바뀌었다 (read -> decodeBinary, encodeByPath -> encodeUsingPath,
 * encodeByMediaType -> encodeUsingMediaType). 이 테스트가 그 경로를 지킨다.
 */
function pngBytes(int $width, int $height): string
{
    $gd = imagecreatetruecolor($width, $height);
    imagefill($gd, 0, 0, imagecolorallocate($gd, 30, 120, 200));
    ob_start();
    imagepng($gd);

    return (string) ob_get_clean();
}

function uploadedPng(int $width, int $height, string $name = 'source.png'): UploadedFile
{
    $path = tempnam(sys_get_temp_dir(), 'orbit-test-').'.png';
    file_put_contents($path, pngBytes($width, $height));

    return new UploadedFile($path, $name, 'image/png', null, true);
}

function storedWidth(string $disk, string $path): int
{
    return (new ImageManager(new Driver))
        ->decodeBinary(Storage::disk($disk)->get($path))
        ->width();
}

it('스케일 다운 한계를 넘는 이미지를 줄이고 실제 저장본에 반영한다', function () {
    Storage::fake('public');

    $attachment = app(MediaLibrary::class)->upload(
        uploadedPng(2000, 1000),
        'media',
        ['storage' => 'public'],
    );

    expect($attachment->kind)->toBe('image')
        ->and($attachment->encoding_status)->toBe('done')
        ->and($attachment->width)->toBe(1200)
        ->and($attachment->height)->toBe(600);

    // 기록된 치수가 아니라 디스크에 실제로 저장된 바이트를 확인한다.
    expect(storedWidth('public', $attachment->physicalPath()))->toBe(1200);
});

it('한계보다 작은 이미지는 원본 치수를 유지한다', function () {
    Storage::fake('public');

    $attachment = app(MediaLibrary::class)->upload(
        uploadedPng(640, 480),
        'media',
        ['storage' => 'public'],
    );

    expect($attachment->width)->toBe(640)
        ->and($attachment->height)->toBe(480)
        ->and(storedWidth('public', $attachment->physicalPath()))->toBe(640);
});

it('branding/favicon 업로드에서 파비콘 변형과 매니페스트를 만든다', function () {
    Storage::fake('public');

    $attachment = app(MediaLibrary::class)->upload(
        uploadedPng(512, 512, 'favicon.png'),
        'branding',
        ['storage' => 'public', 'purpose' => 'favicon'],
    );

    $variants = $attachment->meta['favicon_variants'] ?? [];

    expect($variants)->toHaveKeys(['icon16', 'icon32', 'appleTouch', 'icon192', 'icon512', 'ico', 'manifest']);

    $generated = $attachment->meta['generated_files'] ?? [];
    expect($generated)->toHaveCount(6); // 아이콘 5개 + webmanifest

    foreach ($generated as $path) {
        Storage::disk('public')->assertExists($path);
    }

    // 각 변형이 요청한 정사각 크기로 실제 잘렸는지 확인한다.
    $expectedSizes = ['-16x16.png' => 16, '-32x32.png' => 32, '-apple-touch-icon.png' => 180, '-192x192.png' => 192, '-512x512.png' => 512];

    foreach ($generated as $path) {
        foreach ($expectedSizes as $suffix => $size) {
            if (str_ends_with($path, $suffix)) {
                expect(storedWidth('public', $path))->toBe($size);
            }
        }
    }

    $manifestPath = collect($generated)->first(fn (string $p): bool => str_ends_with($p, '-site.webmanifest'));
    $manifest = json_decode(Storage::disk('public')->get($manifestPath), true);

    expect($manifest['icons'])->toHaveCount(2)
        ->and($manifest['display'])->toBe('standalone');
});
