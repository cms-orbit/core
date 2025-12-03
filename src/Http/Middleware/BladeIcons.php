<?php

namespace CmsOrbit\Core\Http\Middleware;

use Illuminate\Http\Request;
use CmsOrbit\Core\Foundation\Icons\IconFinder;

class BladeIcons
{
    public function __construct(
        private IconFinder $iconFinder
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, \Closure $next): mixed
    {
        $this->iconFinder->setSize('1.25em', '1.25em');

        return $next($request);
    }
}
