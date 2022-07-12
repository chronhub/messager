<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Tracker;

use RuntimeException;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\HasEnvelop;
use Chronhub\Messager\Tracker\TrackerContext;

/** @coversDefaultClass \Chronhub\Messager\Tracker\HasEnvelop */
final class TrackerContextTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $context = $this->newTrackerContext('dispatch');

        $this->assertEquals('dispatch', $context->currentEvent());
        $this->assertFalse($context->isPropagationStopped());
        $this->assertFalse($context->hasException());
        $this->assertNull($context->exception());
    }

    /**
     * @test
     */
    public function it_set_new_event(): void
    {
        $context = $this->newTrackerContext('dispatch');

        $this->assertEquals('dispatch', $context->currentEvent());

        $context->withEvent('finalize');

        $this->assertEquals('finalize', $context->currentEvent());
    }

    /**
     * @test
     */
    public function it_stop_propagation_of_event(): void
    {
        $context = $this->newTrackerContext('dispatch');

        $this->assertFalse($context->isPropagationStopped());

        $context->stopPropagation(true);

        $this->assertTrue($context->isPropagationStopped());
    }

    /**
     * @test
     */
    public function it_set_exception(): void
    {
        $context = $this->newTrackerContext('dispatch');

        $this->assertFalse($context->hasException());
        $this->assertNull($context->exception());

        $exception = new RuntimeException('failed');

        $context->withRaisedException($exception);

        $this->assertTrue($context->hasException());
        $this->assertEquals($exception, $context->exception());
    }

    /**
     * @test
     */
    public function it_can_reset_exception(): void
    {
        $context = $this->newTrackerContext('dispatch');

        $exception = new RuntimeException('failed');

        $context->withRaisedException($exception);

        $this->assertEquals($exception, $context->exception());

        $this->assertTrue($context->resetException());

        $this->assertNull($context->exception());
    }

    public function newTrackerContext(?string $event): TrackerContext
    {
        return new class($event) implements TrackerContext
        {
            use HasEnvelop;
        };
    }
}
