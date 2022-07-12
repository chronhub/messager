<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\AuthorizeMessage;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Subscribers\GuardCommandRoute;
use Chronhub\Messager\Exceptions\UnauthorizedException;

final class GuardCommandRouteTest extends TestCaseWithProphecy
{
    private AuthorizeMessage|ObjectProphecy $authorization;

    private ObjectProphecy|MessageAlias $alias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->authorization = $this->prophesize(AuthorizeMessage::class);
        $this->alias = $this->prophesize(MessageAlias::class);
    }

    /**
     * @test
     */
    public function it_authorize_command(): void
    {
        $eventType = SomeCommand::class;
        $alias = 'some-command';

        $message = new Message(
            SomeCommand::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => $eventType]
        );

        $this->authorization->isNotGranted($alias, $message)->willReturn(false)->shouldBeCalled();
        $this->alias->classToAlias($eventType)->willReturn($alias)->shouldBeCalled();

        $tracker = new TrackMessage();

        $subscriber = new GuardCommandRoute($this->authorization->reveal(), $this->alias->reveal());
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker->fire($context);

        $this->assertFalse($context->isPropagationStopped());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_message_is_not_authorized(): void
    {
        $eventType = SomeCommand::class;
        $alias = 'some-command';

        $this->expectException(UnauthorizedException::class);
        $this->expectExceptionMessage('Unauthorized for event '.$alias);

        $message = new Message(
            SomeCommand::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => $eventType]
        );

        $this->authorization->isNotGranted($alias, $message)->willReturn(true)->shouldBeCalled();
        $this->alias->classToAlias($eventType)->willReturn($alias)->shouldBeCalled();

        $tracker = new TrackMessage();

        $subscriber = new GuardCommandRoute($this->authorization->reveal(), $this->alias->reveal());
        $subscriber->attachToTracker($tracker);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker->fire($context);

        $this->assertTrue($context->isPropagationStopped());
    }
}
