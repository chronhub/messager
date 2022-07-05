<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use React\Promise\PromiseInterface;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;

final class GuardQueryOnDispatch extends GuardQuery
{
    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $promise = $context->promise();

            if ($promise instanceof PromiseInterface) {
                $this->authorizeQuery($context);
            }
        }, OnDispatchPriority::INVOKE_HANDLER->value - 1);
    }
}
