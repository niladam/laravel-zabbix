<?php

namespace Niladam\LaravelZabbix\Tests;

use Niladam\LaravelZabbix\Communication\Response;
use Niladam\LaravelZabbix\Communication\Exceptions\ResponseException;

class ResponseTest extends TestCase
{
    /** @test */
    public function it_can_parse_successful_response(): void
    {
        $response = new Response([
            'response' => 'success',
            'info' => 'processed: 2; failed: 1; total: 3; seconds spent: 0.000059'
        ]);

        $this->assertTrue($response->isSuccess());
        $this->assertEquals(2, $response->getProcessedCount());
        $this->assertEquals(1, $response->getFailedCount());
        $this->assertEquals(3, $response->getTotalCount());
        $this->assertEquals('59 µs', $response->getDuration());
    }

    /** @test */
    public function it_throws_exception_for_invalid_response(): void
    {
        $this->expectException(ResponseException::class);

        new Response([
            'invalid' => 'response'
        ]);
    }

    /** @test */
    public function it_provides_summary_array(): void
    {
        $response = new Response([
            'response' => 'success',
            'info' => 'processed: 2; failed: 1; total: 3; seconds spent: 0.000059'
        ]);

        $summary = $response->getSummary();

        $this->assertEquals([
            'success' => true,
            'humanDuration' => '59 µs',
            'processed' => 2,
            'failed' => 1,
            'total' => 3,
            'duration' => 0.000059,
        ], $summary);
    }
}
