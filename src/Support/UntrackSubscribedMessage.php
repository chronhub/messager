<?php

declare(strict_types=1);

namespace Chronhub\Messager\Support;

use Chronhub\Messager\Tracker\Listener;
use Chronhub\Messager\Tracker\MessageTracker;

trait UntrackSubscribedMessage
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

        $this->listeners = [];
    }
}
