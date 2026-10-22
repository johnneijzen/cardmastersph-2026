<?php

namespace App\Notifications\Channels;

use Illuminate\Notifications\Notification;
use Illuminate\Support\Facades\Log;

/**
 * Delivers notifications to the application log.
 *
 * The deployment cannot send email, so backup health is reported through the
 * log instead. The channel is referenced from `config/backup.php` by its
 * class name.
 */
class LogChannel
{
    /**
     * Send the given notification.
     */
    public function send(mixed $notifiable, Notification $notification): void
    {
        Log::channel('stack')->notice($this->describe($notifiable, $notification), [
            'notification' => $notification::class,
        ]);
    }

    /**
     * Build a readable message for the notification.
     */
    protected function describe(mixed $notifiable, Notification $notification): string
    {
        $message = method_exists($notification, 'toArray')
            ? json_encode($notification->toArray($notifiable))
            : null;

        return trim(class_basename($notification).' '.($message ?: ''));
    }
}
