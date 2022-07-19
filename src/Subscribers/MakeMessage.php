<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Factory\MessageFactory;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class MakeMessage implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    public function __construct(private readonly MessageFactory $messageFactory)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $this->messageFactory->createFromMessage($context->pullTransientMessage());

            $context->withMessage($message);
        }, OnDispatchPriority::MESSAGE_FACTORY->value);
    }
}
