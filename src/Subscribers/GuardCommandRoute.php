<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\AuthorizeMessage;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Exceptions\UnauthorizedException;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class GuardCommandRoute implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    public function __construct(private readonly AuthorizeMessage $authorizationService,
                                private readonly MessageAlias $messageAlias)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $context->message();

            $eventAlias = $this->messageAlias->classToAlias($message->header(Header::EVENT_TYPE->value));

            if ($this->authorizationService->isNotGranted($eventAlias, $message)) {
                $context->stopPropagation(true);

                throw new UnauthorizedException("Unauthorized for event $eventAlias");
            }
        }, OnDispatchPriority::ROUTE->value + 1000);
    }
}
