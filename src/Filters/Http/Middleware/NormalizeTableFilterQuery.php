<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Filters\Http\Middleware;

use Closure;
use CmsOrbit\Core\Filters\FilterQuery;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class NormalizeTableFilterQuery
{
    /**
     * Redirect indexed list-style filter query params to comma-separated scalars.
     *
     * @param Closure(Request): Response $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->isMethod('GET') || ! $request->has('filter')) {
            return $next($request);
        }

        $query = $request->query();
        $normalized = FilterQuery::normalizeQueryParams($query);

        if ($query === $normalized) {
            return $next($request);
        }

        return redirect()->to($request->url().'?'.http_build_query(
            collect($normalized)->except(['page'])->all(),
        ));
    }
}
