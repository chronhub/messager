<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit;

use Throwable;
use RuntimeException;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\HasReporter;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Exceptions\MessageNotHandled;
use Chronhub\Messager\Exceptions\MessageDispatchFailed;
use Chronhub\Messager\Subscribers\CallableMessageSubscriber;

final class HasReporterTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_constructed(): void
    {
        $reporter = $this->reporterInstance();

        $this->assertEquals('anonymous_class', $reporter->name());
    }

    /**
     * @test
     */
    public function it_can_subscribe_to_tracker(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $onDispatch = new CallableMessageSubscriber(
            Reporter::DISPATCH_EVENT,
            function (ContextualMessage $context): void {
                $nameContent = $context->message()->event()->toContent()['name'] ?? null;

                $this->assertEquals('steph', $nameContent);

                $context->withMessage(
                    new Message(SomeCommand::fromContent(['name' => 'bug']))
                );

                $context->markMessageHandled(true);
            },
            1
        );

        $onFinalize = new CallableMessageSubscriber(
            Reporter::FINALIZE_EVENT,
            function (ContextualMessage $context): void {
                $nameContent = $context->message()->event()->toContent()['name'] ?? null;

                $this->assertEquals('bug', $nameContent);
            },
            1
        );

        $reporter = $this->reporterInstance();

        $reporter->subscribe($onDispatch, $onFinalize);

        $reporter->publish($message);
    }

    /**
     * @test
     */
    public function it_raise_wrapped_exception_caught_during_dispatching_message(): void
    {
        $message = new Message(
            SomeCommand::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => SomeCommand::class]
        );

        $reporter = $this->reporterInstance();

        $stopPropagation = new CallableMessageSubscriber(Reporter::DISPATCH_EVENT,
            function (ContextualMessage $context): void {
                $context->stopPropagation(true);

                throw new RuntimeException('some_message');
            }, 1);

        $assertPropagationIsNotStopped = new CallableMessageSubscriber(Reporter::DISPATCH_EVENT,
            function (ContextualMessage $context): void {
                $this->assertFalse($context->isPropagationStopped());
            }, 10);

        $reporter->subscribe($stopPropagation, $assertPropagationIsNotStopped);

        try {
            $reporter->publish($message);
        } catch (Throwable $exception) {
            $this->assertInstanceOf(MessageDispatchFailed::class, $exception);
            $this->assertEquals('some_message', $exception->getPrevious()->getMessage());
        }
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_is_not_handled(): void
    {
        $this->expectException(MessageNotHandled::class);
        $this->expectExceptionMessage('Message '.SomeCommand::class.' not handled');

        $message = new Message(
            SomeCommand::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => SomeCommand::class]
        );

        $reporter = $this->reporterInstance();

        try {
            $reporter->publish($message);
        } catch (MessageDispatchFailed $exception) {
            throw $exception->getPrevious();
        }
    }

    private function reporterInstance(): Reporter
    {
        return new class('anonymous_class') implements Reporter
        {
            use HasReporter;

            public function publish(object|array $message): void
            {
                $context = $this->tracker->newContext(self::DISPATCH_EVENT);

                $context->withMessage($message);

                $this->publishMessage($context);
            }
        };
    }
}
