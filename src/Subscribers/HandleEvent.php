<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class HandleEvent implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            foreach ($context->messageHandlers() as $messageHandler) {
                $messageHandler($context->message()->event());
            }

            $context->markMessageHandled(true);
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
