<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;

final class CallableMessageSubscriber extends AbstractMessageSubscriber
{
    /**
     * @var callable
     */
    private $callback;

    public function __construct(private string $event,
                                callable $callback,
                                private int $priority = 1)
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
