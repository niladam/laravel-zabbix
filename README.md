
# Laravel Zabbix Integration

This package provides a seamless way to send metrics, alerts, and notifications to a Zabbix server within PHP & Laravel applications. It includes powerful abstractions, support for named configurations, and simplified usage via `make` methods.

The package borrows code and is inspired by the [Zabbix sender
](https://github.com/zarplata/zabbix-sender-php) package. Some credits go to [zarplata](https://github.com/zarplata)

---

## Features

- **PHP 7.4+ compatible.**: Works with PHP 7.4 and above.
- **Named Configurations**: Easily switch between multiple host configurations.
- **Streamlined Object Creation**: Utilize `make` methods to simplify object initialization.
- **ZabbixAlert Class**: Extendable class for effortless alerts.
- **Notification Channel**: Integrate Zabbix with Laravel's notification system.
- **Queue Support**: Fully compatible with Laravel queues.
- **Usage outside of Laravel**: Fully functional outside of Laravel.

---

## Installation

You can install the package via composer:

```bash
composer require niladam/laravel-zabbix
```

If using Laravel you can publish the config file with:

```bash
php artisan vendor:publish --provider="Niladam\LaravelZabbix\LaravelZabbixServiceProvider"
```

   This creates `config/laravel-zabbix.php` with the following contents:

```php
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

```
---

## Configuration

Define hosts and default server settings in `config/laravel-zabbix.php`:

```php
return [
    'server' => env('ZABBIX_SERVER', '127.0.0.1'),
    'port' => env('ZABBIX_PORT', 10051),
    'hosts' => [
        'default' => [
            'host_name' => 'default-host',
            'key' => 'default-key',
        ],
        'critical' => [
            'host_name' => 'critical-host',
            'key' => 'critical-key',
        ],
    ],
];
```

Use named configurations like `default` or `critical` in your code.

---

## Usage

### Creating and sending messages (or metrics)

The `make` method simplifies message creation by preloading the configuration and injecting the `ZabbixManager`. Example:

```php
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Message;
use Niladam\LaravelZabbix\Communication\Response;

$manager = app(ZabbixManager::class);

// Create a message with the 'critical' configuration
$criticalMessage = Message::make('critical')->usingValue('Service is down');
$someOtherMessage = Message::make('default')->usingValue('Just another message');

// Send the message
$manager
    ->add($criticalMessage)
    ->add($someOtherMessage);

$response = $manager->send(); // Returns a Response class.

// Some useful methods:
$response->getSummary();            // Get a summary
$response->getDuration();           // Get duration
$response->getTotalCount();         // Get total count
$response->getProcessedCount();     // Get processed count
$response->getFailedCount();        // Get failed count

// The summary looks like:
$summary = [
    "success" => true,
    "humanDuration" => "66 µs",
    "processed" => 1,
    "failed" => 0,
    "total" => 1,
    "duration" => 6.6E-5,
];

// You can also use the message directly:
$response = Message::make('critical')->usingValue('Service is down')->send();
```

## Simplified Alerts with `ZabbixAlert`

The `ZabbixAlert` class is designed for quick and easy alert creation. Extend this class to define alerts with minimal effort.

```php
namespace App\Alerts\Zabbix;

use Niladam\LaravelZabbix\Notifications\ZabbixAlert;

class MyZabbixAlert extends ZabbixAlert
{
    /**
     * @return string|array
     */
    public function getHostConfiguration()
    {
        // Returning a string assumes you have a named configuration.
        return 'my-host-configuration-key-from-config';
        
        // OR
        
        // Returning an array, will use the host details
        return [
            'host_name' => 'your-hostname',
            'key' => 'your-key',
        ];
    }
    
    // If you have a getMessage method on your alert, this will be used.
    public function getMessage(): string
    {
        return 'Oh noes, something happened!';
    }
}

use Illuminate\Support\Facades\Notification;

Notification::sendNow(User::system(), MyZabbixAlert::make('Oh yes, i have changed the message'));
Notification::route('zabbix','',)->notify(MyZabbixAlert::make('Oh yes, i have changed the message'));

// Or simply send it out.
MyZabbixAlert::make()->send();
```

## Laravel Notifications

Integrate Zabbix with Laravel notifications using the `ZabbixChannel`.

### Example Notification

```php
use Illuminate\Notifications\Notification;
use Niladam\LaravelZabbix\Communication\Message;
use Niladam\LaravelZabbix\Notifications\ZabbixChannel;

class ServerDownNotification extends Notification
{
    public function via($notifiable): array
    {
        return [ZabbixChannel::class];
    }

    public function toZabbix($notifiable): Message
    {
        return Message::make('default')->usingValue('Server is down');
    }
}
```

---

## Advanced Usage

```php
use Niladam\LaravelZabbix\ZabbixManager;

$zabbix = app(ZabbixManager::class);

// List the configured hosts
$zabbix->availableHostNames();
$zabbix->getConfig(); // Get the configuration
```

### Temporary Configurations

For one-off configurations, use `usingServer`:

```php
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Message;

$zabbix = app(ZabbixManager::class); // This uses the default server which will be overwritten below.

$zabbix->usingServer([
    'server' => 'temporary-server',
    'port' => 10052,
])
    ->add(
        Message::make()->usingValue('Temporary alert')
    )
    ->add(
        Message::make()
            ->usingHost('another-host')
            ->usingKey('different_key')
            ->usingValue('Something else')
   );

$zabbix->send();
```

---

### Usage outside of Laravel

```php
require 'vendor/autoload.php';

use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Message;

$configuration = [
    'server' => 'my-zabbix-server',
    'port' => 10051,
    'hosts' => [
        'default' => [
            'host_name' => 'my default host',
            'key' => 'the host key',
        ],
    ],
];

$manager = new ZabbixManager($configuration);

Message::setDefaultManager($manager);

$response = Message::make()->usingValue('test using single class')->send();

$response->getSummary();

// OR, multiple.

$manager->add(Message::make()->usingHost('another-host')->usingKey('some-key')->usingValue('test using multiple classes'));

$manager->add(Message::make()->usingConfigurationKey('default')->usingValue('test using configuration key'));

$manager->send();

```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.


## Credits

- [Madalin Tache](https://github.com/niladam)
- [Zarplata](https://github.com/zarplata)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
