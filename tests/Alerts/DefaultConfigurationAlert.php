<?php

namespace Niladam\LaravelZabbix\Tests\Alerts;

use Niladam\LaravelZabbix\Notifications\ZabbixAlert;

class DefaultConfigurationAlert extends ZabbixAlert
{
    /**
     * @inheritDoc
     */
    public function getHostConfiguration(): string
    {
        return 'default';
    }

    public function getMessage(): string
    {
        return 'Test alert message';
    }
}
