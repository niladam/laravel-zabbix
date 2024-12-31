<?php

namespace Niladam\LaravelZabbix\Communication;

use JsonSerializable;
use Webmozart\Assert\Assert;
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\ConfigRepository;
use Niladam\LaravelZabbix\Traits\InteractsWithConfiguration;

class Message implements JsonSerializable
{
    use InteractsWithConfiguration;

    public ?string $host = null;
    public ?string $key = null;
    public ?int $timestamp = null;
    public ?string $value = null;

    protected static ?ZabbixManager $defaultManager = null;
    protected ?ZabbixManager $manager = null;

    protected ?ConfigRepository $config = null;

    public function __construct(
        ?string $configurationKey = 'default'
    ) {
        if (is_null($this->manager)) {
            $this->manager = self::$defaultManager;
        }

        Assert::notNull($this->manager, 'No ZabbixManager instance provided or set as default. Use setDefaultManager() to do that.');

        $this->config = $this->manager->getConfig();

        $this->usingConfigurationKey($configurationKey);
    }

    public static function setDefaultManager(ZabbixManager $manager): void
    {
        self::$defaultManager = $manager;
    }

    /**
     * Make a new instance with the default values set.
     *
     * @param  string|null    $configurationKey
     *
     * @return Message
     */
    public static function make(
        ?string $configurationKey = 'default'
    ): Message {
        return new static($configurationKey);
    }

    /**
     * Set the host name and key from the configuration, using the "default" key as default.
     *
     * @param  string|null  $hostConfigurationKeyName
     *
     * @return static
     */
    public function usingConfigurationKey(?string $hostConfigurationKeyName = null): Message
    {
        $configurationKeyName = $hostConfigurationKeyName ?? 'default';
        $host = $this->fromConfig(sprintf('hosts.%s', $configurationKeyName), []);

        $this->validateDestinationHost($host, $configurationKeyName);

        $this->host = $host['host_name'];

        $this->key = $host['key'];

        return $this;
    }

    /**
     * Set the host for the message.
     *
     * @param  string  $host
     *
     * @return static
     */
    public function usingHost(string $host): Message
    {
        $this->host = $host;

        return $this;
    }

    /**
     * Set the key for the message.
     *
     * @param  string  $key
     *
     * @return static
     */
    public function usingKey(string $key): Message
    {
        $this->key = $key;

        return $this;
    }

    /**
     * Set the value for the message.
     *
     * @param  string  $value
     *
     * @return static
     */
    public function usingValue(string $value): Message
    {
        $this->value = $value;

        return $this;
    }

    /**
     * Set the timestamp for the message.
     *
     * @param  int  $timestamp
     *
     * @return static
     */
    public function usingTimestamp(int $timestamp): Message
    {
        $this->timestamp = $timestamp;

        return $this;
    }

    /**
     * Ensure we have all the required keys set.
     *
     * @return void
     */
    protected function validate(): void
    {
        Assert::notNull($this->config, 'The configuration has not been set. Use setConfig() to do that.');
        Assert::notNull($this->host, 'You have not set the "host" value on your message. Use usingHost() to do that.');
        Assert::notNull($this->key, 'You have not set the "key" value on your message. Use usingKey() to do that.');
        Assert::notNull($this->value, 'You have not set the "value" value on your message. Use usingValue() to do that.');
    }

    public function serialized(): array
    {
        return $this->jsonSerialize();
    }

    public function send()
    {
        $this->manager->add($this);

        return $this->manager->send();
    }

    public function jsonSerialize(): array
    {
        $this->validate();

        return [
            'host' => $this->host,
            'key' => $this->key,
            'value' => $this->value,
            'clock' => $this->timestamp ?? time(),
        ];
    }
}
