<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Tracker;

use Illuminate\Support\Collection;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\Envelop;
use Chronhub\Messager\Tracker\Tracker;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\HasTracker;
use Chronhub\Messager\Tracker\TrackerContext;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tracker\ContextualMessage;

/** @coversDefaultClass \Chronhub\Messager\Tracker\HasTracker */
final class TrackerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_subscribe_to_event(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $this->assertEmpty($tracker->getListeners());

        $callback = function (ContextualMessage $context): void {
            $this->assertEquals('dispatch', $context->currentEvent());
        };

        $tracker->listen('dispatch', $callback, 1);

        $this->assertCount(1, $tracker->getListeners());

        $tracker->fire($context);
    }

    /**
     * @test
     */
    public function it_subscribe_once_to_event_and_forget_listener(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $this->assertEmpty($tracker->getListeners());

        $called = 0;
        $callback = function () use (&$called): void {
            $called++;
        };

        $tracker->listenOnce('dispatch', $callback, 1);

        $this->assertCount(1, $tracker->getListeners());

        $tracker->fire($context);

        $this->assertEquals(1, $called);

        $this->assertCount(0, $tracker->getListeners());

        $tracker->fire($context);

        $this->assertEquals(1, $called);
    }

    /**
     * @test
     */
    public function it_forget_event(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $callback = function (): void { };

        $anotherSubscriber = $tracker->listen('dispatch', $callback, 2);
        $tracker->listen('dispatch', $callback, 1);

        $this->assertCount(2, $tracker->getListeners());

        $tracker->forget($anotherSubscriber);

        $this->assertCount(1, $tracker->getListeners());
    }

    /**
     * @test
     */
    public function it_order_listeners_by_descendant_priorities_on_fire_event(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $cb1 = function (ContextualMessage $context): void {
            $this->assertEquals(['second'], $context->message()->event()->toContent());

            $context->withMessage(new Message(SomeCommand::fromContent(['last'])));
        };

        $cb2 = function (ContextualMessage $context): void {
            $this->assertEquals(['first'], $context->message()->event()->toContent());

            $context->withMessage(new Message(SomeCommand::fromContent(['second'])));
        };

        $cb3 = function (ContextualMessage $context): void {
            $context->withMessage(new Message(SomeCommand::fromContent(['first'])));
        };

        $sub1 = $tracker->listen('dispatch', $cb1, 1);
        $sub2 = $tracker->listen('dispatch', $cb2, 2);
        $sub3 = $tracker->listen('dispatch', $cb3, 3);

        $this->assertCount(3, $tracker->getListeners());

        $this->assertEquals([$sub1, $sub2, $sub3], $tracker->getListeners()->toArray());

        $tracker->fire($context);

        $this->assertEquals(['last'], $context->message()->event()->toContent());
    }

    /**
     * @test
     */
    public function it_stop_propagation_of_event_on_true_callback(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $cb1 = function (ContextualMessage $context): void {
            $this->assertEquals(['second'], $context->message()->event()->toContent());

            $context->withMessage(new Message(SomeCommand::fromContent(['last'])));
        };

        $cb2 = function (ContextualMessage $context): void {
            $this->assertEquals(['first'], $context->message()->event()->toContent());

            $context->withMessage(new Message(SomeCommand::fromContent(['second'])));
        };

        $cb3 = function (ContextualMessage $context): void {
            $context->withMessage(new Message(SomeCommand::fromContent(['first'])));
        };

        $tracker->listen('dispatch', $cb1, 1);
        $tracker->listen('dispatch', $cb2, 2);
        $tracker->listen('dispatch', $cb3, 3);

        $tracker->fireUntil($context, function (ContextualMessage $context): bool {
            return $context->message()->event()->toContent() === ['second'];
        });

        $this->assertFalse($context->isPropagationStopped());
        $this->assertEquals(['second'], $context->message()->event()->toContent());
    }

    /**
     * @test
     */
    public function it_stop_propagation_when_context_is_stopped(): void
    {
        $context = new Envelop('dispatch');

        $tracker = $this->newTrackerInstance($context);

        $cb1 = function (ContextualMessage $context): void {
            $this->assertEquals(['second'], $context->message()->event()->toContent());

            $context->withMessage(new Message(SomeCommand::fromContent(['last'])));
        };

        $cb2 = function (ContextualMessage $context): void {
            $context->withMessage(new Message(SomeCommand::fromContent(['first'])));

            $context->stopPropagation(true);
        };

        $tracker->listen('dispatch', $cb1, 1);
        $tracker->listen('dispatch', $cb2, 2);

        $tracker->fire($context);

        $this->assertTrue($context->isPropagationStopped());
        $this->assertEquals(['first'], $context->message()->event()->toContent());
    }

    private function newTrackerInstance(TrackerContext $context): Tracker
    {
        return new class($context) implements Tracker
        {
            use HasTracker;

            public function __construct(private TrackerContext $context)
            {
                $this->listeners = new Collection();
            }

            public function getListeners(): Collection
            {
                return $this->listeners;
            }
        };
    }
}
