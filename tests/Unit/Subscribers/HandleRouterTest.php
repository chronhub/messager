<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use stdClass;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\Router\Router;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Subscribers\HandleRouter;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Producer\MessageProducer;
use function iterator_to_array;

final class HandleRouterTest extends TestCaseWithProphecy
{
    private Router|ObjectProphecy $router;

    private MessageProducer|ObjectProphecy $producer;

    private TrackMessage $tracker;

    private Message $message;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->prophesize(Router::class);
        $this->producer = $this->prophesize(MessageProducer::class);
        $this->tracker = new TrackMessage();
        $this->message = new Message(new stdClass());
    }

    /**
     * @test
     */
    public function it_handle_message_sync(): void
    {
        $this->producer->isSync($this->message)->willReturn(true)->shouldBeCalled();
        $this->router->route($this->message)->willReturn([function (): void {}])->shouldBeCalled();

        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($this->message);

        $subscriber = new HandleRouter($this->router->reveal(), $this->producer->reveal());
        $subscriber->attachToTracker($this->tracker);

        $this->tracker->fire($context);

        $this->assertEquals($this->message, $context->message());
        $this->assertEquals([function (): void {}], iterator_to_array($context->messageHandlers()));
    }

    /**
     * @test
     */
    public function it_handle_message_async(): void
    {
        $asyncMarkedMessage = new Message(
            SomeCommand::fromContent(['name' => 'steph']),
            [Header::ASYNC_MARKER->value => true]
        );

        $this->producer->isSync($this->message)->willReturn(false)->shouldBeCalled();
        $this->producer->produce($this->message)->willReturn($asyncMarkedMessage)->shouldBeCalled();
        $this->router->route($this->message)->shouldNotBeCalled();

        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($this->message);

        $subscriber = new HandleRouter($this->router->reveal(), $this->producer->reveal());
        $subscriber->attachToTracker($this->tracker);

        $this->tracker->fire($context);

        $this->assertEquals($asyncMarkedMessage, $context->message());
        $this->assertEmpty(iterator_to_array($context->messageHandlers()));
    }
}
