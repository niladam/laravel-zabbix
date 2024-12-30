<?php

namespace Niladam\LaravelZabbix\Notifications;

use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Notifications\Contracts\SentViaZabbix;
use Niladam\LaravelZabbix\Notifications\Contracts\SendsViaZabbix;
use Illuminate\Notifications\Notification;

class ZabbixChannel
{
    public function send(mixed $notifiable, SentViaZabbix $notification): void
    {
        $notification->send();
    }
}
