<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

/**
 * Class IndexController.
 */
class IndexController extends Controller
{
    /**
     * Redirect to the configured index route or show dashboard.
     */
    public function index(): RedirectResponse|View
    {
        $indexRoute = config('orbit.index');
        if ($indexRoute && \Illuminate\Support\Facades\Route::has($indexRoute)) {
            return redirect()->route($indexRoute);
        }

        // Default dashboard view
        return view('orbit::dashboard');
    }

    /**
     * Show the fallback view for undefined routes.
     */
    public function fallback(): View
    {
        return view('settings::errors.404');
    }
}
