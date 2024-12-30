<?php

namespace Niladam\LaravelZabbix\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Notification;

abstract class ZabbixNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via($notifiable): array
    {
        return [
            ZabbixChannel::class
        ];
    }

    abstract public function toZabbix($notifiable);
}
