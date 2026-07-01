<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Components;

use CmsOrbit\Core\Foundation\Notifications\DashboardMessage;
use CmsOrbit\Core\Foundation\Notifications\OrbitMessage;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;

class Notification extends Component
{
    /**
     * @var Authenticatable|null
     */
    public $user;

    /**
     * Create a new component instance.
     */
    public function __construct(Guard $guard)
    {
        $this->user = $guard->user();
    }

    /**
     * Get the view / contents that represent the component.
     *
     * @return View|string
     */
    public function render()
    {
        $notifications = $this->user
            ->unreadNotifications()
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->limit(15)
            ->get();

        return view('orbit::components.notification', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Determine if the component should be rendered.
     */
    public function shouldRender(): bool
    {
        return config('orbit.notifications.enabled', true);
    }
}
