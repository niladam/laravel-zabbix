<?php

namespace Niladam\LaravelZabbix\Communication;

use JsonSerializable;

class Batch implements JsonSerializable
{
    /**
     * @var array
     */
    private array $batch = [];

    public function __construct(string $request = 'sender data')
    {
        $this->batch['request'] = $request;
    }

    public function add(Message $message): void
    {
        $this->batch['data'][] = $message;
    }

    public function getBatch(): array
    {
        return $this->batch;
    }

    public function jsonSerialize(): mixed
    {
        return $this->batch;
    }
}
