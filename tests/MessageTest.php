<?php

namespace Niladam\LaravelZabbix\Tests;

use Niladam\LaravelZabbix\ZabbixManager;
use Niladam\LaravelZabbix\Communication\Message;

class MessageTest extends TestCase
{
    private ZabbixManager $manager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->manager = new ZabbixManager(config('laravel-zabbix'));
        Message::setDefaultManager($this->manager);
    }

    /** @test */
    public function it_can_create_a_message_with_default_configuration(): void
    {
        $message = Message::make();

        $this->assertEquals('default-host', $message->host);
        $this->assertEquals('default-key', $message->key);
    }

    /** @test */
    public function it_can_create_a_message_with_named_configuration(): void
    {
        $message = Message::make('critical');

        $this->assertEquals('critical-host', $message->host);
        $this->assertEquals('critical-key', $message->key);
    }

    /** @test */
    public function it_can_set_custom_host_and_key(): void
    {
        $message = Message::make()
            ->usingHost('custom-host')
            ->usingKey('custom-key');

        $this->assertEquals('custom-host', $message->host);
        $this->assertEquals('custom-key', $message->key);
    }

    /** @test */
    public function it_can_set_value_and_timestamp(): void
    {
        $timestamp = time();
        $message = Message::make()
            ->usingValue('test-value')
            ->usingTimestamp($timestamp);

        $this->assertEquals('test-value', $message->value);
        $this->assertEquals($timestamp, $message->timestamp);
    }

    /** @test */
    public function it_serializes_to_correct_format(): void
    {
        $timestamp = time();
        $message = Message::make()
            ->usingValue('test-value')
            ->usingTimestamp($timestamp);

        $serialized = $message->serialized();

        $this->assertEquals([
            'host' => 'default-host',
            'key' => 'default-key',
            'value' => 'test-value',
            'clock' => $timestamp,
        ], $serialized);
    }
}
