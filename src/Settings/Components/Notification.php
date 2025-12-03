<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Components;

use Illuminate\Contracts\Auth\Guard;
use Illuminate\Contracts\View\View;
use Illuminate\View\Component;
use CmsOrbit\Core\Settings\Notifications\DashboardMessage;

class Notification extends Component
{
    /**
     * @var \Illuminate\Contracts\Auth\Authenticatable|null
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
            ->where('type', DashboardMessage::class)
            ->limit(15)
            ->get();

        return view('settings::components.notification', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Determine if the component should be rendered.
     *
     * @return bool
     */
    public function shouldRender(): bool
    {
        return config('orbit.notifications.enabled', true);
    }
}
