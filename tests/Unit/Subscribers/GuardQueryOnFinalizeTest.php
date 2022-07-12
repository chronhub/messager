<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use React\Promise\Deferred;
use Chronhub\Messager\Reporter;
use React\Promise\PromiseInterface;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\AuthorizeMessage;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Support\HandlePromise;
use Chronhub\Messager\Tests\Double\SomeQuery;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Exceptions\UnauthorizedException;
use Chronhub\Messager\Subscribers\GuardQueryOnFinalize;

final class GuardQueryOnFinalizeTest extends TestCaseWithProphecy
{
    use HandlePromise;

    private AuthorizeMessage|ObjectProphecy $authorization;

    private MessageAlias|ObjectProphecy $alias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorization = $this->prophesize(AuthorizeMessage::class);
        $this->alias = $this->prophesize(MessageAlias::class);
    }

    /**
     * @test
     */
    public function it_authorize_message_on_dispatch_event(): void
    {
        $eventType = SomeQuery::class;
        $eventAlias = 'some-query';

        $message = new Message(
            SomeQuery::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => $eventType]
        );

        $this->alias->classToAlias($eventType)->willReturn($eventAlias)->shouldBeCalled();
        $this->authorization->isNotGranted($eventAlias, $message, ['name' => 'steph'])->willReturn(false)->shouldBeCalled();

        $tracker = new TrackMessage();

        $subscriber = new GuardQueryOnFinalize($this->authorization->reveal(), $this->alias->reveal());
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withMessage($message);
        $context->withPromise($this->providePromise());

        $tracker->fire($context);

        $promise = $context->promise();

        $this->assertEquals(['name' => 'steph'], $this->handlePromise($promise));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_is_not_authorized(): void
    {
        $eventType = SomeQuery::class;
        $alias = 'some-query';

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized for event '.$alias);

        $message = new Message(
            SomeQuery::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => $eventType]
        );

        $this->authorization->isNotGranted($alias, $message, ['name' => 'steph'])->willReturn(true)->shouldBeCalled();
        $this->alias->classToAlias($eventType)->willReturn($alias)->shouldBeCalled();

        $tracker = new TrackMessage();

        $subscriber = new GuardQueryOnFinalize($this->authorization->reveal(), $this->alias->reveal());
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::FINALIZE_EVENT);
        $context->withMessage($message);
        $context->withPromise($this->providePromise());

        $tracker->fire($context);

        $this->assertTrue($context->isPropagationStopped());

        $promise = $context->promise();
        $this->assertInstanceOf(PromiseInterface::class, $promise);

        $this->handlePromise($promise, true);
    }

    private function providePromise(): PromiseInterface
    {
        $deferred = new Deferred();

        $deferred->resolve(['name' => 'steph']);

        return $deferred->promise();
    }
}
