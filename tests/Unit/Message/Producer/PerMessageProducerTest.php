<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Producer;

use stdClass;
use Generator;
use Prophecy\Argument;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Exceptions\RuntimeException;
use Chronhub\Messager\Message\Producer\MessageQueue;
use Chronhub\Messager\Tests\Double\SomeAsyncCommand;
use Chronhub\Messager\Message\Producer\PerMessageProducer;

final class PerMessageProducerTest extends TestCaseWithProphecy
{
    private ObjectProphecy|MessageQueue $producer;

    protected function setUp(): void
    {
        parent::setUp();

        $this->producer = $this->prophesize(MessageQueue::class);
    }

    /**
     * @test
     * @dataProvider provideSync
     */
    public function it_produce_message_synchronously(object $event): void
    {
        $message = new Message($event);

        $this->producer->toQueue($message)->shouldNotBeCalled();

        $producer = new PerMessageProducer($this->producer->reveal());

        $this->assertTrue($producer->isSync($message));

        $this->assertEquals($message, $producer->produce($message));
    }

    /**
     * @test
     */
    public function it_produce_message_asynchronously(): void
    {
        $event = SomeAsyncCommand::fromContent(['name' => 'steph']);

        $message = new Message($event, [
            Header::ASYNC_MARKER->value => false,
        ]);

        $this->producer->toQueue(Argument::type(Message::class))->shouldBeCalled();

        $producer = new PerMessageProducer($this->producer->reveal());

        $this->assertFalse($producer->isSync($message));

        $asyncMessage = $producer->produce($message);

        $this->assertNotEquals($message, $asyncMessage);

        $this->assertTrue($asyncMessage->header(Header::ASYNC_MARKER->value));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_async_marker_header_does_not_exists_from_is_sync_method(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Async marker header is required to produce message sync/async for event');

        $producer = new PerMessageProducer($this->producer->reveal());

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $producer->isSync($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_async_marker_header_does_not_exists_from_produce_method(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Async marker header is required to produce message sync/async for event');

        $producer = new PerMessageProducer($this->producer->reveal());

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $producer->produce($message);
    }

    public function provideSync(): Generator
    {
        yield [new stdClass()];

        $event = (SomeCommand::fromContent(['name' => 'steph']));

        yield [$event->withHeader(Header::ASYNC_MARKER->value, true)];

        $event = SomeAsyncCommand::fromContent(['name' => 'steph']);

        yield [$event->withHeader(Header::ASYNC_MARKER->value, true)];
    }
}
