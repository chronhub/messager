<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Throwable;
use React\Promise\Deferred;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class HandleQuery implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            if ($messageHandler = $context->messageHandlers()->current()) {
                $event = $context->message()->event();

                $deferred = new Deferred();

                try {
                    $messageHandler($event, $deferred);
                } catch (Throwable $exception) {
                    $deferred->reject($exception);
                } finally {
                    $context->withPromise($deferred->promise());
                    $context->markMessageHandled(true);
                }
            }
        }, OnDispatchPriority::INVOKE_HANDLER->value);
    }
}
