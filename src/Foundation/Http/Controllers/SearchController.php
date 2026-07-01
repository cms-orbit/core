<?php

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Support\Facades\Orbit;
use Illuminate\Contracts\View\View;

class SearchController
{
    /**
     * Display a search result view.
     */
    public function search(?string $query = null): View
    {
        return view('orbit::partials.search.results', [
            'results' => Orbit::search($query),
            'query' => $query,
        ]);
    }
}
