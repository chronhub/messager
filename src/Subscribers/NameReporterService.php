<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class NameReporterService implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    public function __construct(private readonly string $reporterServiceName)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $context->message();

            if ($message->hasNot(Header::REPORTER_NAME->value)) {
                $context->withMessage(
                    $message->withHeader(Header::REPORTER_NAME->value, $this->reporterServiceName)
                );
            }
        }, OnDispatchPriority::MESSAGE_FACTORY->value - 1);
    }
}
