<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Factory\MessageFactory;

final class MakeMessage implements MessageSubscriber
{
    public function __construct(private MessageFactory $factory)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $this->factory->createFromMessage($context->pullTransientMessage());

            $context->withMessage($message);
        }, OnDispatchPriority::MESSAGE_FACTORY->value);
    }
}
