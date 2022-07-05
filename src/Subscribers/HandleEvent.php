<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;

final class HandleEvent extends AbstractMessageSubscriber
{
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
