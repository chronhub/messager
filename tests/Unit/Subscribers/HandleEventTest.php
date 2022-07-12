<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Tests\Double\SomeEvent;
use Chronhub\Messager\Subscribers\HandleEvent;

final class HandleEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_handle_event(): void
    {
        $message = new Message(SomeEvent::fromContent(['name' => 'steph']));

        $handled = false;
        $messageHandler = function () use (&$handled): void {
            $handled = true;
        };

        $tracker = new TrackMessage();

        $subscriber = new HandleEvent();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);
        $context->withMessageHandlers([$messageHandler]);

        $tracker->fire($context);

        $this->assertTrue($handled);
        $this->assertTrue($context->isMessageHandled());
    }

    /**
     * @test
     */
    public function it_handle_event_with_multiple_handlers(): void
    {
        $message = new Message(SomeEvent::fromContent(['name' => 'steph']));

        $handled = [false, false];
        $oneHandler = function () use (&$handled): void {
            $handled[0] = true;
        };

        $secondHandler = function () use (&$handled): void {
            $handled[1] = true;
        };

        $tracker = new TrackMessage();

        $subscriber = new HandleEvent();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);
        $context->withMessageHandlers([$oneHandler, $secondHandler]);

        $tracker->fire($context);

        $this->assertEquals([true, true], $handled);
        $this->assertTrue($context->isMessageHandled());
    }

    /**
     * @test
     */
    public function it_mark_message_handled_when_event_handlers_is_empty(): void
    {
        $message = new Message(SomeEvent::fromContent(['name' => 'steph']));

        $tracker = new TrackMessage();

        $subscriber = new HandleEvent();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $this->assertEmpty(iterator_to_array($context->messageHandlers()));

        $tracker->fire($context);

        $this->assertEmpty(iterator_to_array($context->messageHandlers()));

        $this->assertTrue($context->isMessageHandled());
    }
}
