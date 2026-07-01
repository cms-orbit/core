<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use Illuminate\Http\RedirectResponse;

/**
 * Class IndexController.
 */
class IndexController extends Controller
{
    /**
     * Redirect to the configured index route.
     */
    public function index(): RedirectResponse
    {
        return redirect()->route(config('orbit.index'));
    }

    /**
     * Fallback for undefined routes within the Orbit panel.
     */
    public function fallback(): void
    {
        abort(404);
    }
}
