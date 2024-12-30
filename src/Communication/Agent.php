<?php

namespace Niladam\LaravelZabbix\Communication;

use JsonException;
use RuntimeException;
use Niladam\LaravelZabbix\Communication\Exceptions\NetworkException;
use Niladam\LaravelZabbix\Communication\Exceptions\ResponseException;

/**
 * The following class borrows code heavily from zarplata/zabbix-sender
 * but modified to improve/simplify the API.
 *
 * @see https://github.com/zarplata/zabbix-sender-php
 */
class Agent
{
    /**
     * Instance instances array
     *
     * @var array
     */
    protected static array $instances = [];

    /**
     *  Zabbix protocol header
     *
     * @var string
     */
    private const HEADER = 'ZBXD';

    /**
     *  Zabbix protocol version
     *
     * @var int
     */
    private const VERSION = 1;

    /**
     * Zabbix server response header length
     * https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen
     *
     * @var int
     */
    private const RESPONSE_HEADER_LENGTH = 13;

    /**
     * @var string|null
     */
    private ?string $serverAddress;

    /**
     * @var int|null
     */
    private ?int $serverPort;

    /**
     * @var bool Disable send operation
     */
    private bool $disabled = false;

    /**
     * Create singletone object
     *
     * @param  string|null  $name  Name of object
     *
     * @return Agent instance
     */
    public static function instance(?string $name = 'default'): Agent
    {
        if (!isset(self::$instances[$name])) {
            self::$instances[$name] = new static($name);
        }

        return self::$instances[$name];
    }

    public function __construct(
        string $serverAddress,
        int $serverPort = 10051
    ) {
        $this->serverAddress = $serverAddress;
        $this->serverPort = $serverPort;
    }

    /**
     * Configure the sender.
     *
     * @param  array  $options
     *
     * @return self
     */
    public function configure(array $options = []): Agent
    {
        if (isset($options['server_address'])) {
            $this->serverAddress = $options['server_address'];
        }

        if (isset($options['server_port'])) {
            $this->serverPort = (int) $options['server_port'];
        }

        if (isset($options['disable'])) {
            $this->disabled = (bool) $options['disable'];
        }

        return $this;
    }

    /**
     * Disable sender functionality. It may be necessary if you want
     * switch off send metrics but you don't want remove the code
     * from your project.
     *
     * @return void
     */
    public function disable(): void
    {
        $this->disabled = true;
    }

    /**
     * Enable sender functionality. This is reverse operation of `disable()`
     *
     * @return void
     */
    public function enable(): void
    {
        $this->disabled = false;
    }

    /**
     * Send the batch of messages to Zabbix server through network socket
     *
     * @param  Batch  $batch        Batch of messages
     * @param  bool   $getResponse  If you want the response summary back.
     *
     * @throws RuntimeException
     * @throws JsonException
     * @throws NetworkException
     * @throws ResponseException
     *
     * @return bool|Response
     */
    public function send(Batch $batch, bool $getResponse = true)
    {
        if ($this->disabled) {
            return false;
        }

        $payload = $this->makePayload($batch);
        $payloadLength = strlen($payload);

        $socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if (!$socket) {
            throw new RuntimeException("can't create TCP socket");
        }

        $socketConnected = socket_connect(
            $socket,
            $this->serverAddress,
            $this->serverPort
        );

        if (!$socketConnected) {
            throw new NetworkException(
                sprintf(
                    "can't connect to %s:%d",
                    $this->serverAddress,
                    $this->serverPort
                )
            );
        }

        $bytesCount = socket_send(
            $socket,
            $payload,
            $payloadLength,
            0
        );

        switch (true) {
            case !$bytesCount:
                throw new NetworkException(
                    sprintf(
                        "can't send %d bytes to zabbix server %s:%d",
                        $payloadLength,
                        $this->serverAddress,
                        $this->serverPort
                    )
                );

            case $bytesCount != $payloadLength:
                throw new NetworkException(
                    sprintf(
                        'incorrect count of bytes %s sended, expected: %d',
                        $bytesCount,
                        $payloadLength
                    )
                );

            default:
                break;
        }

        $response = $this->checkResponse($socket);

        return $getResponse ? $response : true;
    }

    /**
     * Make payload for Zabbix server with special Zabbix header
     * and datalen
     *
     * https://www.zabbix.com/documentation/3.4/manual/appendix/protocols/header_datalen
     */
    private function makePayload(Batch $batch): string
    {
        $encodedPacket = json_encode($batch, JSON_THROW_ON_ERROR);

        return pack(
            'a4CPa*',
            self::HEADER,
            self::VERSION,
            strlen($encodedPacket),
            $encodedPacket
        );
    }

    /**
     * Handle the Zabbix server response.
     *
     * @param $socket
     *
     * @throws NetworkException
     * @throws ResponseException
     * @throws JsonException
     *
     * @return Response
     */
    private function checkResponse($socket): Response
    {
        $responseBuffer = '';
        $responseBufferLength = 2048;

        $bytesCount = socket_recv(
            $socket,
            $responseBuffer,
            $responseBufferLength,
            0
        );

        if (!$bytesCount) {
            throw new NetworkException(
                "can't receive response from socket"
            );
        }

        $responseWithoutHeader = substr(
            $responseBuffer,
            self::RESPONSE_HEADER_LENGTH
        );

        $response = json_decode($responseWithoutHeader, true, 512, JSON_THROW_ON_ERROR);

        switch (true) {
            case $response === null:
            case $response === false:
                throw new ResponseException(
                    sprintf(
                        "can't decode zabbix server response %s, reason: %s",
                        $responseWithoutHeader,
                        json_last_error_msg()
                    )
                );

            default:
                break;
        }

        $zabbixResponse = new Response($response);

        if (!$zabbixResponse->isSuccess()) {
            throw new ResponseException(
                'zabbix server returned non-success response'
            );
        }

        return $zabbixResponse;
    }
}
