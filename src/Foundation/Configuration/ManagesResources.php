<?php

namespace CmsOrbit\Core\Foundation\Configuration;

use CmsOrbit\Core\Support\Locale;
use Illuminate\Support\Arr;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\Str;
use RuntimeException;

trait ManagesResources
{
    use ManagesPackage;

    /**
     * Collection of JS and CSS resources for the panel.
     */
    protected array $registeredResources = [];

    /**
     * Register a resource with the given key.
     *
     * @param  string|array  $value
     */
    public function registerResource(string $key, $value): static
    {
        $item = Arr::get($this->registeredResources, $key, []);

        $this->registeredResources[$key] = array_merge($item, Arr::wrap($value));

        return $this;
    }

    /**
     * Return CSS\JS.
     *
     * @param  null  $key
     * @return array|Collection|mixed
     */
    public function getResource($key = null)
    {
        return collect($this->registeredResources)
            ->when($key !== null, fn (Collection $resources) => $resources->get($key));
    }

    /**
     * Determine published assets are up-to-date.
     *
     * @throws RuntimeException
     */
    public function assetsAreCurrent(): bool
    {
        $publishedPath = public_path('vendor/orbit/manifest.json');

        throw_unless(File::exists($publishedPath), new RuntimeException('Orbit assets are not published. Please run: `php artisan orbit:publish`'));

        return File::get($publishedPath) === File::get(__DIR__.'/../../../public/manifest.json');
    }

    public static function vite(): \Illuminate\Foundation\Vite
    {
        return Vite::useBuildDirectory('vendor/orbit')
            ->useManifestFilename('manifest.json')
            ->useStyleTagAttributes(['data-turbo-track' => 'reload'])
            ->useScriptTagAttributes(['data-turbo-track' => 'reload'])
            ->withEntryPoints(['resources/js/app.js', 'resources/sass/app.scss'])
            ->createAssetPathsUsing(function (string $path, ?bool $secure) {

                if (Locale::isRtl() && Str::endsWith($path, '.css')) {
                    $path = Str::replaceLast('.css', '.rtl.css', $path);
                }

                return asset($path, $secure);
            });
    }
}
