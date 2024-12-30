<?php

namespace Niladam\LaravelZabbix;

use JsonException;
use Niladam\LaravelZabbix\Communication\Batch;
use Niladam\LaravelZabbix\Communication\Agent;
use Niladam\LaravelZabbix\Communication\Message;
use Niladam\LaravelZabbix\Communication\Response;
use Niladam\LaravelZabbix\Traits\InteractsWithConfiguration;
use Niladam\LaravelZabbix\Communication\Exceptions\NetworkException;
use Niladam\LaravelZabbix\Communication\Exceptions\ResponseException;

class ZabbixManager
{
    use InteractsWithConfiguration;

    /**
     * The Agent instance
     *
     * @var Agent|null
     */
    protected ?Agent $agent = null;

    /**
     * The host configuration that will be used
     *
     * @var array
     */
    protected array $host = [];

    /**
     * The messages that will be sent.
     *
     * @var array
     */
    private array $messages = [];

    /**
     * Hold the configuration repository
     *
     * @var ConfigRepository
     */
    private ConfigRepository $config;

    public function __construct(array $configuration = [])
    {
        $this->config = new ConfigRepository($configuration);

        $this->usingDefaultServer();

        Message::setDefaultManager($this);
    }

    public function usingServer(array $configuration): ZabbixManager
    {
        return $this->setServer($configuration);
    }

    public function add(Message $message): ZabbixManager
    {
        $this->messages[] = $message;

        return $this;
    }

    /**
     * Send the messages to Zabbix.
     *
     * @throws NetworkException
     * @throws ResponseException
     * @throws JsonException
     *
     * @return bool|Response
     */
    public function send(?bool $getResponse = true)
    {
        try {
            $response = $this->agent->send($this->getBatch());

            return $getResponse
                ? $response
                : true;
        } catch (Throwable $e) {
            throw $e;
        }
    }

    public function getConfig(): ConfigRepository
    {
        return $this->config;
    }

    protected function getBatch(): Batch
    {
        $batch = new Batch();

        /**
         * @var array<Message> $messages
         * @var Message        $message
         */
        foreach ($this->messages as $message) {
            $batch->add($message);
        }

        return $batch;
    }

    private function usingDefaultServer(): ZabbixManager
    {
        $this->setServer([
            'server' => $this->fromConfig('server'),
            'port' => $this->fromConfig('port', 10051)
        ]);

        return $this;
    }

    private function setServer(array $configuration): ZabbixManager
    {
        $this->validateServer($configuration);

        $this->agent = new Agent($configuration['server'], $configuration['port']);

        return $this;
    }
}
