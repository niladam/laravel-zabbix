<?php

return [
    /**
     * Your Zabbix server hostname.
     *
     * Just the hostname of the server.
     */
    'server' => env('ZABBIX_SERVER'),

    /**
     * Your zabbix port.
     *
     * If different from the default 10051.
     */
    'port' => env('ZABBIX_PORT', 10051),

    /**
     * Available hosts.
     *
     * Eeach key in the hosts array must contain the host_name and key keys.
     */
    'hosts' => [
        'default' => [
            'host_name' => env('ZABBIX_DEFAULT_HOSTNAME'),
            'key' => env('ZABBIX_DEFAULT_KEY'),
        ],
    ],
];
