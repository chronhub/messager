<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use Generator;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Subscribers\MakeMessage;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Factory\MessageFactory;

final class MakeMessageTest extends TestCaseWithProphecy
{
    private ObjectProphecy|MessageFactory $factory;

    private TrackMessage $tracker;

    protected function setUp(): void
    {
        parent::setUp();

        $this->factory = $this->prophesize(MessageFactory::class);
        $this->tracker = new TrackMessage();
    }

    /**
     * @test
     * @dataProvider provideEvent
     */
    public function it_create_message_from_context_transient_message(array|object $event): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->factory->createFromMessage($event)->willReturn($message)->shouldBeCalled();

        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withTransientMessage($event);

        $subscriber = new MakeMessage($this->factory->reveal());
        $subscriber->attachToTracker($this->tracker);

        $this->tracker->fire($context);

        $this->assertEquals($message, $context->message());
    }

    public function provideEvent(): Generator
    {
        yield [
            'headers' => [Header::EVENT_TYPE->value => SomeCommand::class],
            'content' => ['name' => 'steph'],
        ];

        $event = SomeCommand::fromContent(['name' => 'steph']);

        yield [$event];

        yield [new Message($event)];
    }
}
