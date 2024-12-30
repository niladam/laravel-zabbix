<?php

namespace Niladam\LaravelZabbix\Communication;

use Niladam\LaravelZabbix\Communication\Exceptions\ResponseException;

class Response
{
    private const SUCCESS_RESPONSE = 'success';

    /**
     * @var string|null
     */
    private ?string $responseStatus;

    /**
     * @var int|null
     */
    private ?int $processedItems;

    /**
     * @var int|null
     */
    private ?int $failedItems;

    /**
     * @var int|null
     */
    private ?int $totalItems;

    /**
     * @var float|null
     */
    private ?float $duration;

    private ?string  $humanDuration;

    public function __construct(array $response)
    {
        $this->parseZabbixResponse($response);
    }

    public function isSuccess(): bool
    {
        return $this->responseStatus === self::SUCCESS_RESPONSE;
    }

    public function getProcessedCount(): int
    {
        return $this->processedItems;
    }

    public function getFailedCount(): int
    {
        return $this->failedItems;
    }

    public function getTotalCount(): int
    {
        return $this->totalItems;
    }

    public function getDuration(): string
    {
        return $this->convertToHumanReadableTime($this->duration);
    }

    public function getSummary(): array
    {
        return [
            'success' => $this->isSuccess(),
            'humanDuration' => $this->getDuration(),
            'processed' => $this->getProcessedCount(),
            'failed' => $this->getFailedCount(),
            'total' => $this->getTotalCount(),
            'duration' => $this->duration,
        ];
    }

    /**
     * Parse array to Response class properties
     *
     * This method takes array of values through argument
     * check required fields - `response` and `info` and
     * trying to find information about processed items
     * to zabbix server through reqular expression.
     *
     * @param  array  $response
     *
     * @throws ResponseException
     * @return void
     *
     */
    private function parseZabbixResponse(array $response): void
    {
        if (!isset($response['response'])) {
            throw new ResponseException(
                'invalid zabbix server response, missing `response` field'
            );
        }

        $this->responseStatus = $response['response'];

        if (!isset($response['info'])) {
            throw new ResponseException(
                'invalid zabbix server response, missing `info` field'
            );
        }

        $pattern = '/\w+: (\d+); \w+: (\d+); \w+: (\d+); [a-z ]+: (\d+\.\d+)/';
        $matches = [];

        $matched = preg_match(
            $pattern,
            $response['info'],
            $matches
        );

        switch (true) {
            case $matched === false:
                throw new ResponseException(
                    sprintf(
                        "can't decode info into values, preg_match error: %d",
                        preg_last_error()
                    )
                );

            case $matched === 0:
                throw new ResponseException(
                    sprintf(
                        "pattern '%s' didn't satisfy to subject '%s'",
                        $pattern,
                        $response['info']
                    )
                );

            default:
                break;
        }

        /*
         * $matches must contains the following values:
         *
         * $matches[0] - whole matched string for example:
         * processed: 2; failed: 0; total: 2; seconds spent: 0.000059
         *
         * $matches[1] - 2 (processed)
         * $matches[2] - 0 (failed)
         * $matches[3] - 2 (total)
         * $matches[4] - 0.000059 (seconds spent)
         */
        $this->processedItems = (int) $matches[1];
        $this->failedItems = (int) $matches[2];
        $this->totalItems = (int) $matches[3];
        $this->duration = (float) $matches[4];
        $this->humanDuration = $this->convertToHumanReadableTime($matches[4]);
    }

    public function convertToHumanReadableTime(float $seconds): string
    {
        $units = [
            'ns' => 1e-9,
            'µs' => 1e-6,
            'ms' => 1e-3,
            's' => 1,
        ];

        foreach ($units as $unit => $threshold) {
            if ($seconds < $threshold * 1e3) {
                return round($seconds / $threshold, 3) . " $unit";
            }
        }

        // If value exceeds seconds, it's already in seconds (unlikely here)
        return $seconds . ' s';
    }
}
