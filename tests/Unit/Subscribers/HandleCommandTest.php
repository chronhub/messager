<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Subscribers\HandleCommand;

final class HandleCommandTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_handle_command(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $handled = false;
        $messageHandler = function () use (&$handled): void {
            $handled = true;
        };

        $tracker = new TrackMessage();

        $subscriber = new HandleCommand();
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
    public function it_does_not_mark_message_handled_when_context_message_handlers_is_empty(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $tracker = new TrackMessage();

        $subscriber = new HandleCommand();
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker->fire($context);

        $this->assertFalse($context->isMessageHandled());
    }
}
