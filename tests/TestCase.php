<?php

namespace Niladam\LaravelZabbix\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Niladam\LaravelZabbix\LaravelZabbixServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            LaravelZabbixServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('laravel-zabbix', [
            'server' => 'zabbix.test',
            'port' => 10051,
            'hosts' => [
                'default' => [
                    'host_name' => 'default-host',
                    'key' => 'default-key',
                ],
                'critical' => [
                    'host_name' => 'critical-host',
                    'key' => 'critical-key',
                ],
            ],
        ]);
    }
}
