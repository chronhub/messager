<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Decorator\MessageDecorator;
use Chronhub\Messager\Subscribers\ChainMessageDecoratorSubscriber;

final class ChainMessageDecoratorSubscriberTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_chain_message_decorators(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));
        $decoratedMessage = $message->withHeader('some', 'header');

        $tracker = new TrackMessage();

        $decorator = $this->prophesize(MessageDecorator::class);
        $decorator->decorate($message)->willReturn($decoratedMessage)->shouldBeCalled();

        $subscriber = new ChainMessageDecoratorSubscriber($decorator->reveal());

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $subscriber->attachToTracker($tracker);

        $tracker->fire($context);

        $this->assertEquals(['some' => 'header'], $context->message()->headers());
    }
}
