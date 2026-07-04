<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Analytics\Http\Middleware;

use Closure;
use CmsOrbit\Core\Analytics\AnalyticsTracker;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CaptureAnalytics
{
    public function __construct(
        protected AnalyticsTracker $tracker,
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $this->tracker->track($request, $response);

        return $response;
    }
}
