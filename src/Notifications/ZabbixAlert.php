<?php

namespace Niladam\LaravelZabbix\Notifications;

use JsonException;
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Message;
use Niladam\LaravelZabbix\Communication\Response;
use Niladam\LaravelZabbix\Notifications\Contracts\SentViaZabbix;
use Niladam\LaravelZabbix\Communication\Exceptions\NetworkException;
use Niladam\LaravelZabbix\Communication\Exceptions\ResponseException;

abstract class ZabbixAlert extends ZabbixNotification implements SentViaZabbix
{
    protected ?ZabbixManager $manager = null;

    public function __construct(
        ?string $message = null
    ) {
        $this->manager = app(ZabbixManager::class);

        if (
            is_null($message)
            && method_exists($this, 'getMessage')
            && $this->getMessage() !== null
        ) {
            $message = $this->getMessage();
        }

        $this->manager->add(
            $this->toMessage($message)
        );
    }

    protected function toMessage(string $message): Message
    {
        $hostConfiguration = $this->getValidConfiguration();

        return Message::make()
            ->usingHost($hostConfiguration['host_name'])
            ->usingKey($hostConfiguration['key'])
            ->usingValue($message);
    }

    protected function getValidConfiguration(): array
    {
        $hostConfiguration = $this->getHostConfiguration();

        $configuration = is_string($hostConfiguration)
            ? $this->manager->fromConfig(sprintf('hosts.%s', $hostConfiguration), [])
            : $hostConfiguration;

        $this->manager->validateDestinationHost($configuration);

        return $configuration;
    }

    public static function make(?string $message = null): ZabbixAlert
    {
        return new static($message);
    }

    public function usingServer(array $configuration): ZabbixAlert
    {
        $this->manager->usingServer($configuration);

        return $this;
    }

    /**
     * @throws JsonException
     * @throws NetworkException
     * @throws ResponseException
     *
     * @return bool|Response
     */
    public function send(): mixed
    {
        return $this->manager->send();
    }

    /**
     * This is used internally by Laravel's notification system.
     *
     * @param $notifiable
     *
     * @return $this
     */
    public function toZabbix($notifiable): ZabbixAlert
    {
        return $this;
    }
}
