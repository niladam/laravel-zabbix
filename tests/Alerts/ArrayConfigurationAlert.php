<?php

namespace Niladam\LaravelZabbix\Tests\Alerts;

use Niladam\LaravelZabbix\Notifications\ZabbixAlert;

class ArrayConfigurationAlert extends ZabbixAlert
{
    /**
     * @inheritDoc
     */
    public function getHostConfiguration(): array
    {
        return [
            'host_name' => 'test-host',
            'key' => 'test-key'
        ];
    }
}
