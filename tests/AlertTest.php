<?php

namespace Niladam\LaravelZabbix\Tests;

use Mockery;
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Response;
use Niladam\LaravelZabbix\Tests\Alerts\ArrayConfigurationAlert;
use Niladam\LaravelZabbix\Tests\Alerts\DefaultConfigurationAlert;

class AlertTest extends TestCase
{
    protected function getZabbixConfig(): array
    {
        return [
            'server' => 'zabbix.test',
            'hosts' => [
                'default' => [
                    'host_name' => 'default-host',
                    'key'       => 'default-key',
                ],
                'critical' => [
                    'host_name' => 'critical-host',
                    'key'       => 'critical-key',
                ],
            ],
        ];
    }

    /**
     * Sets up a partial mock for ZabbixManager with the typical expectations,
     * then binds it to the container so your alerts can resolve it automatically.
     */
    protected function mockZabbixManager(): void
    {
        $realManager = new ZabbixManager($this->getZabbixConfig());

        $managerMock = Mockery::mock($realManager)->makePartial();
        $managerMock
            ->shouldReceive('add')
            ->once()
            ->andReturnSelf();

        $managerMock
            ->shouldReceive('validateDestinationHost')
            ->once();

        $managerMock
            ->shouldReceive('send')
            ->once()
            ->andReturn(new Response([
                'response' => 'success',
                'info'     => 'processed: 1; failed: 0; total: 1; seconds spent: 0.000059',
            ]));

        $this->app->instance(ZabbixManager::class, $managerMock);
    }

    /** @test */
    public function it_can_send_alert_with_default_configuration_from_class(): void
    {
        $this->mockZabbixManager();

        $alert = DefaultConfigurationAlert::make();
        $response = $alert->send();
        $responseSummary = $response->getSummary();

        $this->assertTrue($response->isSuccess());
        $this->assertIsArray($responseSummary);
        $this->assertSame($alert->getAlertMessage(), $alert->getMessage());
    }

    /** @test */
    public function it_can_send_alert_with_default_configuration_from_class_with_custom_message(): void
    {
        $this->mockZabbixManager();

        $alert = DefaultConfigurationAlert::make('My custom message');
        $response = $alert->send();
        $responseSummary = $response->getSummary();

        $this->assertTrue($response->isSuccess());
        $this->assertIsArray($responseSummary);
        $this->assertSame($alert->getAlertMessage(), 'My custom message');
    }

    /** @test */
    public function it_can_send_alert_with_configuration_from_array(): void
    {
        $this->mockZabbixManager();

        $alert = ArrayConfigurationAlert::make('My custom message');
        $response = $alert->send();
        $responseSummary = $response->getSummary();

        $this->assertTrue($response->isSuccess());
        $this->assertIsArray($responseSummary);
        $this->assertSame($alert->getAlertMessage(), 'My custom message');
    }
}
