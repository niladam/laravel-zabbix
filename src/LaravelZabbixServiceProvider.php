<?php

namespace Niladam\LaravelZabbix;

use Illuminate\Support\ServiceProvider;
use Niladam\LaravelZabbix\Notifications\ZabbixChannel;

class LaravelZabbixServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->publishes([
            dirname(__DIR__) . '/config/laravel-zabbix.php' => config_path('laravel-zabbix.php'),
        ], 'laravel-zabbix');
    }

    public function boot(): void
    {
        $this->mergeConfigFrom(dirname(__DIR__) . '/config/laravel-zabbix.php', 'laravel-zabbix');

        $this->app->when(ZabbixManager::class)
            ->needs('configuration')
            ->give(fn ($app) => $app['config']->get('laravel-zabbix'));

        $this->app->singleton(
            ZabbixManager::class,
            fn ($app) => new ZabbixManager($app['config']->get('laravel-zabbix'))
        );

        $this->app->bind('laravel-zabbix', ZabbixManager::class);
    }
}
