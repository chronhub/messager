<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Decorator\MessageDecorator;

final class ChainMessageDecoratorSubscriber extends AbstractMessageSubscriber
{
    public function __construct(private MessageDecorator $messageDecorator)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $context->withMessage(
                $this->messageDecorator->decorate($context->message())
            );
        }, OnDispatchPriority::MESSAGE_DECORATOR->value);
    }
}
