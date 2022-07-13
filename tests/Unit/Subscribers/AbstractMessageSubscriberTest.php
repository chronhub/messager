<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Subscribers\AbstractMessageSubscriber;
use Chronhub\Messager\Tracker\TrackMessage;

final class AbstractMessageSubscriberTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_forget_listeners(): void
    {
        $subscriber = $this->messageSubscriberInstance();

        $this->assertEmpty($subscriber->getListeners());

        $tracker = new TrackMessage();

        $subscriber->attachToTracker($tracker);

        $this->assertCount(2, $subscriber->getListeners());

        $subscriber->detachFromTracker($tracker);

        $this->assertEmpty($subscriber->getListeners());
    }

    private function messageSubscriberInstance(): AbstractMessageSubscriber
    {
        return new class() extends AbstractMessageSubscriber
        {
            public function attachToTracker(MessageTracker $tracker): void
            {
               $this->listeners[] = $tracker->listen('foo', function (): void {});
               $this->listeners[] = $tracker->listen('foo', function (): void {});
            }

            public function getListeners(): array
            {
                return $this->listeners;
            }
        };
    }
}
