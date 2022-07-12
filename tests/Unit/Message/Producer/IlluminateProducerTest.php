<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Producer;

use stdClass;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Producer\MessageJob;
use Chronhub\Messager\Message\Producer\IlluminateQueue;
use Chronhub\Messager\Message\Serializer\MessageSerializer;

final class IlluminateProducerTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_handle_message_to_queue(): void
    {
        $queue = $this->prophesize(QueueingDispatcher::class);
        $serializer = $this->prophesize(MessageSerializer::class);

        $producer = new IlluminateQueue($queue->reveal(), $serializer->reveal(), 'default', 'default');

        $message = new Message(new stdClass(), [Header::REPORTER_NAME->value => 'some_bus']);

        $payload = [
            'headers' => [],
            'content' => ['foo' => 'bar'],
        ];

        $serializer->serializeMessage($message)->willReturn($payload)->shouldBeCalled();

        $job = new MessageJob([
            'headers' => [],
            'content' => ['foo' => 'bar'],
        ], 'some_bus', 'default', 'default');

        $queue->dispatchToQueue($job)->shouldBeCalled();

        $producer->toQueue($message);
    }
}
