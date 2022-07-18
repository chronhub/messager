<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Tracker;

use Error;
use stdclass;
use Generator;
use React\Promise\PromiseInterface;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\Envelop;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use function iterator_to_array;

final class EnvelopTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $context = new Envelop('dispatch');

        $this->assertNull($context->promise());
        $this->assertFalse($context->isMessageHandled());
        $this->assertEmpty(iterator_to_array($context->messageHandlers()));
    }

    /**
     * @test
     * @dataProvider provideTransientMessages
     */
    public function it_set_transient_message(object|array $message): void
    {
        $context = new Envelop('dispatch');

        $context->withTransientMessage($message);

        $this->assertEquals($message, $context->transientMessage());
    }

    /**
     * @test
     * @dataProvider provideTransientMessages
     */
    public function it_pull_transient_message(array|object $message): void
    {
        $context = new Envelop('dispatch');

        $context->withTransientMessage($message);

        $this->assertEquals($message, $context->transientMessage());

        $this->assertEquals($message, $context->pullTransientMessage());

        $this->assertNull($context->transientMessage());
    }

    /**
     * @test
     */
    public function it_raise_exception_while_pulling_transient_message_which_has_not_been_initialized(): void
    {
        $this->expectException(Error::class);

        $context = new Envelop('dispatch');

        $context->pullTransientMessage();
    }

    /**
     * @test
     */
    public function it_can_set_message(): void
    {
        $context = new Envelop('dispatch');

        $context->withTransientMessage(['some' => 'content']);

        $context->pullTransientMessage();

        $message = new Message(SomeCommand::fromContent(['some' => 'content']));

        $context->withMessage($message);

        $this->assertequals($message, $context->message());
    }

    /**
     * @test
     */
    public function it_mark_message_is_handled(): void
    {
        $context = new Envelop('dispatch');

        $this->assertFalse($context->isMessageHandled());

        $context->markMessageHandled(true);

        $this->assertTrue($context->isMessageHandled());
    }

    /**
     * @test
     */
    public function it_set_promise(): void
    {
        $context = new Envelop('dispatch');

        $this->assertNull($context->promise());

        $promise = $this->createMock(PromiseInterface::class);

        $context->withPromise($promise);

        $this->assertEquals($promise, $context->promise());
    }

    /**
     * @test
     */
    public function it_set_message_handlers(): void
    {
        $context = new Envelop('dispatch');

        $this->assertEmpty(iterator_to_array($context->messageHandlers()));

        $messageHandlers = [function (): void {}];

        $context->withMessageHandlers($messageHandlers);

        $this->assertEquals($messageHandlers, iterator_to_array($context->messageHandlers()));
    }

    public function provideTransientMessages(): Generator
    {
        yield [new Message(new stdclass())];

        yield [['some' => 'message']];
    }
}
