<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Frontend;

use CmsOrbit\Core\Models\Concerns\HasSeo;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Response;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;
use RalphJSmit\Laravel\SEO\Support\ImageMeta;
use RalphJSmit\Laravel\SEO\Support\SEOData;

/**
 * Frontend Handler
 * 
 * 테마를 사용하지 않는 경우의 기본 Frontend 렌더러
 * SEO 자동 지원
 */
class FrontendHandler
{
    /**
     * Render view with SEO support
     *
     * @param string $view
     * @param array $data
     * @param SEOData|Model|null $seoData
     * @return Response|InertiaResponse
     */
    public static function render(
        string $view,
        array $data = [],
        SEOData|Model|null $seoData = null
    ): Response|InertiaResponse {
        $seoData = static::prepareSeoData($seoData);

        // Check if Inertia is available
        if (class_exists(Inertia::class)) {
            return static::renderInertia($view, $data, $seoData);
        }

        // Fallback to Blade
        return static::renderBlade($view, $data, $seoData);
    }

    /**
     * Render with Inertia
     *
     * @param string $view
     * @param array $data
     * @param SEOData $seoData
     * @return InertiaResponse
     */
    protected static function renderInertia(string $view, array $data, SEOData $seoData): InertiaResponse
    {
        Inertia::share('pageName', $seoData->title);
        Inertia::share('siteName', config('orbit.seo.site_name'));

        return Inertia::render($view, $data)->withViewData([
            'seoData' => $seoData,
        ]);
    }

    /**
     * Render with Blade
     *
     * @param string $view
     * @param array $data
     * @param SEOData $seoData
     * @return Response
     */
    protected static function renderBlade(string $view, array $data, SEOData $seoData): Response
    {
        return response()->view($view, array_merge($data, [
            'seoData' => $seoData,
        ]));
    }

    /**
     * Prepare SEO data
     *
     * @param SEOData|Model|null $seoData
     * @return SEOData
     */
    protected static function prepareSeoData(SEOData|Model|null $seoData): SEOData
    {
        // If model has HasSeo trait, convert it
        if ($seoData instanceof Model) {
            $uses = class_uses_recursive($seoData);
            
            if (in_array(HasSeo::class, $uses)) {
                $seoData = $seoData->toSeoData();
            } else {
                $seoData = new SEOData();
            }
        }

        // Create new if null
        if (!$seoData) {
            $seoData = new SEOData();
        }

        // Fill defaults from config
        $seoData->description ??= config('orbit.seo.description');
        $seoData->tags ??= explode(',', config('orbit.seo.keywords', ''));
        $seoData->author ??= config('orbit.seo.author');
        $seoData->image ??= config('orbit.seo.default_image');
        $seoData->imageMeta ??= $seoData->image ? new ImageMeta($seoData->image) : null;
        $seoData->url ??= url()->current();
        $seoData->favicon ??= config('orbit.seo.favicon');
        $seoData->type ??= config('orbit.seo.og_type', 'website');
        $seoData->published_time ??= Cache::rememberForever(
            md5(request()->url()),
            fn() => Carbon::now()
        );
        $seoData->modified_time ??= Cache::rememberForever(
            md5(request()->url() . '_modified'),
            fn() => Carbon::now()
        );
        $seoData->section ??= 'Page';
        $seoData->twitter_username ??= config('orbit.seo.twitter_creator');
        $seoData->site_name = config('orbit.seo.site_name');
        $seoData->locale = app()->getLocale();
        $seoData->robots ??= config('orbit.seo.robots', 'index, follow');
        $seoData->openGraphTitle ??= config('orbit.seo.og_site_name');

        return $seoData;
    }
}

