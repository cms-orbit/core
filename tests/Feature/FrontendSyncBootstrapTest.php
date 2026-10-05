<?php

declare(strict_types=1);

use CmsOrbit\Core\Frontend\FrontendSync;
use Illuminate\Filesystem\Filesystem;

beforeEach(function (): void {
    $this->hostPath = sys_get_temp_dir().'/orbit-bootstrap-'.bin2hex(random_bytes(8));
    mkdir($this->hostPath.'/vendor/cms-orbit/core/resources/orbit', recursive: true);
    mkdir($this->hostPath.'/resources/js', recursive: true);
    copy(__DIR__.'/../../resources/orbit/frontend.json', $this->hostPath.'/vendor/cms-orbit/core/resources/orbit/frontend.json');
    file_put_contents($this->hostPath.'/package.json', '{"type":"module"}');
    file_put_contents($this->hostPath.'/resources/js/app.js', "import './bootstrap';\n");
    file_put_contents($this->hostPath.'/vite.config.js', <<<'JS'
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';

export default defineConfig({
    plugins: [laravel({ input: ['resources/css/app.css', 'resources/js/app.js'] })],
});
JS);
});

afterEach(function (): void {
    (new Filesystem)->deleteDirectory($this->hostPath);
});

it('prepares a React entry and build dependencies on a plain Laravel host', function (): void {
    (new FrontendSync($this->hostPath))->sync();

    expect(file_get_contents($this->hostPath.'/resources/js/app.tsx'))
        ->toContain('createInertiaApp', "import.meta.glob('./pages/**/*.tsx')", 'createRoot(el)');
    expect(file_get_contents($this->hostPath.'/resources/js/app.js'))->toBe("import './bootstrap';\n");
    expect(file_get_contents($this->hostPath.'/vite.config.js'))
        ->toContain("from '@vitejs/plugin-react'", 'orbitReact()', 'orbitWayfinder()', "'resources/js/app.tsx'", "'resources/js/app.js'");
    expect(json_decode(file_get_contents($this->hostPath.'/package.json'), true)['devDependencies'])
        ->toHaveKeys(['@vitejs/plugin-react', '@laravel/vite-plugin-wayfinder', '@types/react', '@types/react-dom']);
});

it('keeps a host owned React entry and plugin configuration', function (): void {
    $entry = "// Host-owned Inertia setup\n";
    file_put_contents($this->hostPath.'/resources/js/app.tsx', $entry);
    $vite = str_replace('plugins: [', 'plugins: [customReactPlugin(), ', file_get_contents($this->hostPath.'/vite.config.js'));
    file_put_contents($this->hostPath.'/vite.config.js', $vite);

    (new FrontendSync($this->hostPath))->sync(true);

    expect(file_get_contents($this->hostPath.'/resources/js/app.tsx'))->toBe($entry);
    expect(file_get_contents($this->hostPath.'/vite.config.js'))
        ->toContain('customReactPlugin()')->not->toContain('orbitReact()');
});

it('can synchronize a plain host repeatedly without duplicate entries or plugins', function (): void {
    $sync = new FrontendSync($this->hostPath);
    $sync->sync();
    $vite = file_get_contents($this->hostPath.'/vite.config.js');
    $entry = file_get_contents($this->hostPath.'/resources/js/app.tsx');

    $sync->sync();

    expect(file_get_contents($this->hostPath.'/vite.config.js'))->toBe($vite);
    expect(file_get_contents($this->hostPath.'/resources/js/app.tsx'))->toBe($entry);
});
