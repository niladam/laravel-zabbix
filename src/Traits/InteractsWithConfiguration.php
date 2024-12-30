<?php

namespace Niladam\LaravelZabbix\Traits;

use Webmozart\Assert\Assert;
use Niladam\LaravelZabbix\ConfigRepository;
use Niladam\LaravelZabbix\ZabbixManager;

trait InteractsWithConfiguration
{
    public function validateDestinationHost(array $host): void
    {
        $availableHosts = $this->availableHostNames();

        Assert::isNonEmptyMap(
            $host,
            sprintf(
                'Invalid configuration. Configured hosts are: %s (or you can specify an array that contains host_name and key keys).',
                implode(', ', $availableHosts)
            )
        );

        Assert::keyExists($host, 'host_name', 'Your host does not contain the "host_name" key.');
        Assert::keyExists($host, 'key', 'Your host does not contain the key "key".');
    }

    public function validateServer(array $server): void
    {
        Assert::keyExists(
            $server,
            'server',
            'The provided server configuration does not contain the server.'
        );

        Assert::notNull($server['server'], 'The key "server" configuration value is empty.');

        Assert::keyExists(
            $server,
            'port',
            'The provided server configuration does not contain the port.'
        );

        Assert::notNull($server['port'], 'The port configuration value is empty.');
    }

    /**
     * List available hosts
     *
     * @return array
     */
    public function availableHostNames(): array
    {
        $availableHosts = $this->fromConfig('hosts');

        return array_keys($availableHosts);
    }

    public function fromConfig(string $key, $default = null)
    {
        return $this->config->get($key, $default);
    }
}
