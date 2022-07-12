<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Producer;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Illuminate\Contracts\Queue\Queue;
use Illuminate\Contracts\Container\Container;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Producer\MessageJob;

final class MessageJobTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_handle_queued_message(): void
    {
        $job = new MessageJob(['foo' => 'bar'], 'bus_name', 'default', 'default');

        $reporter = $this->prophesize(Reporter::class);
        $reporter->publish(['foo' => 'bar'])->shouldBeCalled();

        $container = $this->prophesize(Container::class);
        $container->make('bus_name')->willReturn($reporter->reveal());

        $job->handle($container->reveal());
    }

    /**
     * @test
     */
    public function it_can_queue_payload(): void
    {
        $job = new MessageJob(['foo' => 'bar'], 'bus_name', 'default', 'some_queue');

        $queue = $this->prophesize(Queue::class);
        $queue->pushOn('some_queue', $job)->shouldBeCalled();

        $job->queue($queue->reveal(), $job);
    }

    /**
     * @test
     */
    public function it_can_access_to_message_name(): void
    {
        $payload = [
            'headers' => [Header::EVENT_TYPE->value => 'some_name'],
            'payload' => ['foo' => 'bar'],
        ];

        $job = new MessageJob($payload, 'bus_name', null, null);

        $this->assertEquals('some_name', $job->displayName());
    }
}
