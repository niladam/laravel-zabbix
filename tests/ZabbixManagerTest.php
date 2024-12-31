<?php

namespace Niladam\LaravelZabbix\Tests;

use Mockery;
use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Agent;
use Niladam\LaravelZabbix\Communication\Message;
use Niladam\LaravelZabbix\Communication\Response;

class ZabbixManagerTest extends TestCase
{
    private ZabbixManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ZabbixManager(config('laravel-zabbix'));
    }

    /** @test */
    public function it_can_send_single_message(): void
    {
        $agentMock = Mockery::mock(Agent::class);
        $agentMock->shouldReceive('send')
            ->once()
            ->andReturn(new Response([
                'response' => 'success',
                'info' => 'processed: 1; failed: 0; total: 1; seconds spent: 0.000059'
            ]));

        $this->setProtectedProperty($this->manager, 'agent', $agentMock);

        $message = Message::make()
            ->usingValue('test message');

        $response = $this->manager
            ->add($message)
            ->send();

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(1, $response->getProcessedCount());
        $this->assertEquals(0, $response->getFailedCount());
    }

    /** @test */
    public function it_can_send_multiple_messages(): void
    {
        $agentMock = Mockery::mock(Agent::class);
        $agentMock->shouldReceive('send')
            ->once()
            ->andReturn(new Response([
                'response' => 'success',
                'info' => 'processed: 2; failed: 0; total: 2; seconds spent: 0.000059'
            ]));

        $this->setProtectedProperty($this->manager, 'agent', $agentMock);

        $message1 = Message::make()->usingValue('test message 1');
        $message2 = Message::make('critical')->usingValue('test message 2');

        $response = $this->manager
            ->add($message1)
            ->add($message2)
            ->send();

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(2, $response->getProcessedCount());
        $this->assertEquals(0, $response->getFailedCount());
    }

    /** @test */
    public function it_can_use_custom_server_configuration(): void
    {
        $this->manager->usingServer([
            'server' => 'custom.zabbix.test',
            'port' => 10052,
        ]);

        $agentMock = Mockery::mock(Agent::class);
        $agentMock->shouldReceive('send')
            ->once()
            ->andReturn(new Response([
                'response' => 'success',
                'info' => 'processed: 1; failed: 0; total: 1; seconds spent: 0.000059'
            ]));

        $this->setProtectedProperty($this->manager, 'agent', $agentMock);

        $response = $this->manager
            ->add(Message::make()->usingValue('test'))
            ->send();

        $this->assertTrue($response->isSuccess());
    }

    private function setProtectedProperty($object, string $property, $value): void
    {
        $reflection = new \ReflectionClass(get_class($object));
        $property = $reflection->getProperty($property);
        $property->setAccessible(true);
        $property->setValue($object, $value);
    }
}
