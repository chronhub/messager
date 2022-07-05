<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Tracker\Listener;
use Chronhub\Messager\Tracker\MessageTracker;

abstract class AbstractMessageSubscriber implements MessageSubscriber
{
    /**
     * @var Listener[]
     */
    protected array $listeners = [];

    public function detachFromTracker(MessageTracker $tracker): void
    {
        foreach ($this->listeners as $listener) {
            $tracker->forget($listener);
        }
    }
}
