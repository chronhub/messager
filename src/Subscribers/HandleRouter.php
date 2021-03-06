<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Router\Router;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Producer\MessageProducer;

final class HandleRouter extends AbstractMessageSubscriber
{
    public function __construct(private Router $router,
                                private MessageProducer $messageProducer)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $this->messageProducer->isSync($context->message())
                ? $this->handleSyncMessage($context)
                : $this->handleAsyncMessage($context);
        }, OnDispatchPriority::ROUTE->value);
    }

    private function handleSyncMessage(ContextualMessage $context): void
    {
        $context->withMessageHandlers(
            $this->router->route($context->message())
        );
    }

    private function handleAsyncMessage(ContextualMessage $context): void
    {
        $asyncMessage = $this->messageProducer->produce($context->message());

        $context->withMessage($asyncMessage);
    }
}
