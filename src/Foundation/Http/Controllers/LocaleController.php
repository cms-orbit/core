<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Support\Locale;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Switches the admin interface locale. The choice is persisted in the session
 * and, for authenticated users, on their `locale` attribute so it survives
 * across devices.
 */
class LocaleController extends Controller
{
    public function switch(Request $request): RedirectResponse
    {
        $locale = (string) $request->input('locale');

        if (in_array($locale, Locale::supported(), true)) {
            $request->session()->put('orbit.locale', $locale);

            $user = $request->user();

            if ($user !== null && $user->isFillable('locale')) {
                $user->forceFill(['locale' => $locale])->save();
            }
        }

        return back();
    }
}
