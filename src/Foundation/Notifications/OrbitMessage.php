<?php

declare(strict_types=1);

namespace CmsOrbit\Core\Foundation\Notifications;

use Carbon\Carbon;
use CmsOrbit\Core\Support\Color;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Notifications\Notification;

class OrbitMessage extends Notification implements Arrayable
{
    /**
     * The data that should be stored with the notification.
     */
    public array $data = [];

    /**
     * Create a new instance of OrbitMessage with the given data.
     *
     * @param  array  $data  An array of data to be merged with default message data.
     */
    public function __construct(array $data = [])
    {
        $default = [
            'title' => '',
            'action' => '#', // URL for the "View" button
            'message' => '',
            'time' => Carbon::now(), // The timestamp this message was created.
            'type' => Color::INFO->name(), // The type of message (INFO, WARNING, SUCCESS, or ERROR).
        ];

        $this->data = array_merge($default, $data);
    }

    /**
     * Create a new element.
     *
     * @param  mixed  ...$arguments
     */
    public static function make(...$arguments): static
    {
        return new static(...$arguments);
    }

    /**
     * Set the title of the message.
     *
     * @param  string  $title  The title to be set.
     * @return $this
     */
    public function title(string $title): self
    {
        $this->data['title'] = $title;

        return $this;
    }

    /**
     * Set the message content.
     *
     * @param  string  $message  The message text to be set.
     * @return $this
     */
    public function message(string $message): self
    {
        $this->data['message'] = $message;

        return $this;
    }

    /**
     * Set the URL for the "View" button. This is the URL that the user will be redirected to
     * when they click the "View" button on this notification in the orbit.
     *
     * @param  string  $action  The URL to be set.
     * @return $this
     */
    public function action(string $action): self
    {
        $this->data['action'] = $action;

        return $this;
    }

    /**
     * Set the type of the message.
     *
     * @param  Color  $color  The color representing the new type of the message.
     * @return $this
     */
    public function type(Color $color): self
    {
        $this->data['type'] = $color->name();

        return $this;
    }

    /**
     * Get the notification channels.
     *
     *
     * @return array
     */
    public function via(mixed $notifiable)
    {
        return [OrbitChannel::class];
    }

    /**
     * Get the instance as an array for orbit.
     */
    public function toOrbit(): array
    {
        return $this->toArray();
    }

    /**
     * Get the instance as an array for orbit (alias for toOrbit).
     */
    public function toDashboard(): array
    {
        return $this->toArray();
    }

    /**
     * Convert the notification instance to an array.
     */
    public function toArray(): array
    {
        return $this->data;
    }
}
