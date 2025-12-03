<?php

namespace CmsOrbit\Core\Http\Controllers;

use Illuminate\Contracts\View\View;
use CmsOrbit\Core\Support\Facades\Dashboard;

class SearchController
{
    /**
     * Display a search result view.
     */
    public function search(?string $query = null): View
    {
        return view('settings::partials.search-result', [
            'results' => Dashboard::search($query),
            'query'   => $query,
        ]);
    }
}
