<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Support\Facades;

use CmsOrbit\Core\Foundation\Orbit as OrbitKernel;
use CmsOrbit\Core\Screen\Screen;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Facade;

/**
 * Class Orbit.
 *
 * @method static Collection  getSearch()
 * @method static Collection  getPermission()
 * @method static Collection  getAllowAllPermission()
 * @method static string      version()
 * @method static string      prefix(string $path = '')
 * @method        static      configure(array $options)
 * @method        static      option(string $key, ?string $default = null)
 * @method static mixed       modelClass(string $key, string $default = null)
 * @method static string      model(string $key, string $default = null)
 * @method        static      useModel(string $key, string $custom)
 * @method static bool        checkUpdate()
 * @method static self        setCurrentScreen(Screen $screen, bool $partialRequest = false)
 * @method static Screen|null getCurrentScreen()
 * @method static bool        isPartialRequest()
 * @method static self        registerSection(string $key, string $icon, ?string $label = null, int $sort = 5000, array $placement = [])
 * @method static array<string, array{
 *     icon: string,
 *     label: ?string,
 *     sort: int,
 *     url: ?string,
 *     placement: array{
 *         rail?: 'top'|'bottom',
 *         sidebar?: 'top'|'bottom',
 *         topbar?: 'left'|'right'
 *     }
 * }> getSections()
 */
class Orbit extends Facade
{
    /**
     * Get the registered name of the component.
     *
     * @return string
     */
    protected static function getFacadeAccessor()
    {
        return OrbitKernel::class;
    }
}
