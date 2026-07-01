<?php

namespace CmsOrbit\Core\Foundation\Http\Controllers;

use CmsOrbit\Core\Foundation\Notifications\DashboardMessage;
use CmsOrbit\Core\Foundation\Notifications\OrbitMessage;
use CmsOrbit\Core\Support\Facades\Toast;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;
use Illuminate\View\View;

/**
 * Handles user notifications.
 */
class NotificationController extends Controller
{
    /**
     * Display latest user notifications.
     *
     *
     * @return View
     */
    public function index(Request $request)
    {
        /** @var Collection|DatabaseNotification[] $notifications */
        $notifications = $request->user()
            ->notifications()
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->cursorPaginate();

        return view('orbit::partials.notification.notification', [
            'notifications' => $notifications,
        ]);
    }

    /**
     * Mark a notification as read and redirect.
     *
     *
     * @return RedirectResponse
     */
    public function markAsRead(string $id, Request $request)
    {
        /** @var DatabaseNotification $notification */
        $notification = $request->user()
            ->notifications()
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->where('id', $id)
            ->firstOrFail();

        $notification->markAsRead();

        $redirectUrl = $notification->data['action'] ?? url()->previous();

        return redirect($redirectUrl);
    }

    /**
     * Mark all user notifications as read.
     */
    public function markAllAsRead(Request $request): RedirectResponse
    {
        $request->user()
            ->unreadNotifications
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->markAsRead();

        Toast::info(__('All messages have been read.'));

        return back();
    }

    /**
     * Get the count of unread user notifications for dashboard and Orbit messages.
     */
    public function unreadCount(Request $request): array
    {
        $total = $request->user()
            ->unreadNotifications
            ->whereIn('type', [OrbitMessage::class, DashboardMessage::class])
            ->count();

        return [
            'total' => $total,
        ];
    }
}
