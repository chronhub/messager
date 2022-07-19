<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Support\UntrackSubscribedMessage;

final class CallableMessageSubscriber implements MessageSubscriber
{
    use UntrackSubscribedMessage;

    /**
     * @var callable
     */
    private $callback;

    public function __construct(private readonly string $event,
                                callable $callback,
                                private readonly int $priority = 1)
    {
        $this->callback = $callback;
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen($this->event, function (ContextualMessage $context): void {
            ($this->callback)($context);
        }, $this->priority);
    }
}
