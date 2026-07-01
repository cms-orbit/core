<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Screen\Layouts\Listener;
use CmsOrbit\Core\Screen\Screen;
use Illuminate\Contracts\Container\BindingResolutionException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Crypt;

class AsyncController extends Controller
{
    /**
     * @return mixed
     *
     * @throws \Throwable
     */
    public function load(Request $request)
    {
        $request->validate([
            '_call' => 'required|string',
            '_screen' => 'required|string',
            '_template' => 'required|string',
        ]);

        $screen = Crypt::decryptString(
            $request->input('_screen')
        );

        /** @var Screen $screen */
        $screen = app($screen);

        return response()->json($screen->asyncBuildArray(
            $request->input('_call'),
            $request->input('_template')
        ));
    }

    /**
     * @return mixed
     *
     * @throws BindingResolutionException
     * @throws \ReflectionException
     */
    public function listener(Request $request, string $screen, string $layout)
    {
        $screen = Crypt::decryptString($screen);
        $layout = Crypt::decryptString($layout);

        /** @var Screen $screen */
        $screen = app($screen);

        /** @var Listener $layout */
        $layout = app($layout);

        return response()->json($screen->asyncPartialLayoutArray($layout, $request));
    }
}
