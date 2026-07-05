<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Providers;

use CmsOrbit\Core\Analytics\AnalyticsDashboard;
use CmsOrbit\Core\Analytics\AnalyticsGeoLocator;
use CmsOrbit\Core\Analytics\AnalyticsTracker;
use CmsOrbit\Core\Analytics\Http\Middleware\CaptureAnalytics;
use CmsOrbit\Core\Analytics\MaxMindCountryResolver;
use CmsOrbit\Core\Support\Facades\Config as OrbitConfig;
use Illuminate\Contracts\Http\Kernel;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Support\ServiceProvider;

class AnalyticsServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(AnalyticsDashboard::class);
        $this->app->singleton(AnalyticsTracker::class);
        $this->app->singleton(MaxMindCountryResolver::class);
        $this->app->singleton(AnalyticsGeoLocator::class);

        $this->registerAnalyticsConfigGroup();
    }

    public function boot(): void
    {
        EncryptCookies::except([
            AnalyticsTracker::VISITOR_COOKIE,
            AnalyticsTracker::VISIT_COOKIE,
        ]);

        $this->app->make(Kernel::class)->pushMiddleware(CaptureAnalytics::class);
    }

    protected function registerAnalyticsConfigGroup(): void
    {
        OrbitConfig::registerGroup('Analytics', 650, [
            'icon'        => 'bs.bar-chart',
            'title'       => '방문 통계',
            'description' => 'Orbit에 기본 내장된 가벼운 방문 분석을 설정합니다.',
            'hubSection'  => 'user',
        ]);

        OrbitConfig::registerSection('Analytics', 'collection', [
            'title'    => '수집',
            'priority' => 20,
        ]);

        OrbitConfig::registerSection('Analytics', 'privacy', [
            'title'    => '개인정보 및 보존',
            'priority' => 10,
        ]);

        OrbitConfig::registerItem('Analytics', 'analytics.enabled', 'switcher', true, 'collection', [
            'title'       => '방문 통계 수집',
            'description' => '일반 웹 페이지 요청에 대한 페이지뷰와 방문 지표를 저장합니다.',
        ]);

        OrbitConfig::registerItem('Analytics', 'analytics.exclude_admin_routes', 'switcher', true, 'collection', [
            'title'       => '관리자 페이지 제외',
            'description' => 'Orbit 대시보드와 설정 화면은 기본적으로 방문 집계에서 제외합니다.',
        ]);

        OrbitConfig::registerItem('Analytics', 'analytics.filter_bots', 'switcher', true, 'privacy', [
            'title'       => '봇 트래픽 제외',
            'description' => '일반적인 크롤러와 봇으로 식별되는 요청은 저장하지 않습니다.',
        ]);

        OrbitConfig::registerItem('Analytics', 'analytics.respect_do_not_track', 'switcher', true, 'privacy', [
            'title'       => 'Do Not Track 존중',
            'description' => '브라우저가 추적 거부(DNT)를 요청하면 방문 정보를 저장하지 않습니다.',
        ]);

        OrbitConfig::registerItem('Analytics', 'analytics.retention_days', 'number', 90, 'privacy', [
            'title'       => '보존 기간 (일)',
            'description' => '이 기간보다 오래된 집계 로그는 하루에 한 번 자동으로 정리됩니다.',
        ]);
    }
}
