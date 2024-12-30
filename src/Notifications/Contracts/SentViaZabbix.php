<?php

namespace Niladam\LaravelZabbix\Notifications\Contracts;

use Niladam\LaravelZabbix\Communication\Response;

interface SentViaZabbix
{
    /**
     * Get the host configuration for the message.
     *
     * This method should return either a named configuration key or a structured array with the host details.
     *
     * Example:
     * - Return a string for a named configuration: `'critical'`
     * - Or return an array:
     *   [
     *      'host_name' => 'example-host',
     *      'key' => 'example-key',
     *   ]
     *
     * @return string|array
     */
    public function getHostConfiguration();

    /**
     * @return bool|Response
     */
    public function send(): mixed;

    public function toZabbix($notifiable);
}
