<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use stdClass;
use Prophecy\Argument;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tracker\TrackMessage;
use Illuminate\Contracts\Validation\Factory;
use Chronhub\Messager\Tracker\MessageTracker;
use Illuminate\Contracts\Validation\Validator;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Subscribers\ValidateCommand;
use Chronhub\Messager\Exceptions\ReportingMessageFailed;
use Chronhub\Messager\Exceptions\ValidationMessageFailed;
use Chronhub\Messager\Tests\Double\SomeCommandToValidate;
use Chronhub\Messager\Tests\Double\SomeCommandToPreValidate;

final class ValidateCommandTest extends TestCaseWithProphecy
{
    private Factory|ObjectProphecy $validation;

    private Validator|ObjectProphecy $validator;

    private MessageTracker $tracker;

    public function setUp(): void
    {
        parent::setUp();

        $this->validation = $this->prophesize(Factory::class);
        $this->validator = $this->prophesize(Validator::class);
        $this->tracker = new TrackMessage();
    }

    /**
     * @test
     */
    public function it_does_not_validate_event_which_is_not_instance_of_messaging(): void
    {
        $this->validation->make([], [])->shouldNotBeCalled();

        $message = new Message(new stdClass());

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_does_not_validate_event_which_is_not_instance_of_validation_message(): void
    {
        $this->validation->make([], [])->shouldNotBeCalled();

        $message = new Message(SomeCommand::fromContent([]));

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_async_marker_missing_in_headers(): void
    {
        $this->expectException(ReportingMessageFailed::class);
        $this->expectExceptionMessage('Missing async marker for event some-command-to-validate');

        $message = new Message(
            SomeCommandToValidate::fromContent(['name' => 'steph']),
            [Header::EVENT_TYPE->value => 'some-command-to-validate']
        );

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_pre_validate_event_content_with_false_async_marker(): void
    {
        $this->validation
            ->make(['name' => 'steph'], ['name' => 'required'])
            ->willReturn($this->validator)
            ->shouldBeCalled();

        $this->validator->fails()->willReturn(false)->shouldBeCalled();

        $event = SomeCommandToPreValidate::fromContent(['name' => 'steph']);
        $message = new Message($event, [Header::ASYNC_MARKER->value => false]);

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_does_not_pre_validate_event_content_with_true_async_marker(): void
    {
        $this->validation->make([], [])->shouldNotBeCalled();

        $message = new Message(
            SomeCommandToPreValidate::fromContent([]),
            [Header::ASYNC_MARKER->value => true]
        );

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_validate_event_content_with_true_async_marker(): void
    {
        $this->validation
            ->make(['name' => 'steph'], ['name' => 'required'])
            ->willReturn($this->validator)
            ->shouldBeCalled();

        $this->validator->fails()->willReturn(false)->shouldBeCalled();

        $message = new Message(
            SomeCommandToValidate::fromContent(['name' => 'steph']),
            [Header::ASYNC_MARKER->value => true]
        );

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_does_not_validate_event_content_with_false_async_marker(): void
    {
        $this->validation->make([], [])->shouldNotBeCalled();

        $message = new Message(
            SomeCommandToValidate::fromContent([]),
            [Header::ASYNC_MARKER->value => false]
        );

        $this->dispatchMessage($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_when_validation_fails(): void
    {
        $this->expectException(ValidationMessageFailed::class);
        $this->expectExceptionMessage('Validation fails');

        $this->validation
            ->make(Argument::type('array'), ['name' => 'required'])
            ->willReturn($this->validator)
            ->shouldBeCalled();

        $this->validator->fails()->willReturn(true)->shouldBeCalled();
        $this->validator->errors()->willReturn('Validation fails')->shouldBeCalled();

        $message = new Message(
            SomeCommandToValidate::fromContent([]),
            [Header::ASYNC_MARKER->value => true]
        );

        $this->dispatchMessage($message);
    }

    private function dispatchMessage(Message $message): void
    {
        $subscriber = new ValidateCommand($this->validation->reveal());
        $subscriber->attachToTracker($this->tracker);

        $context = $this->tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $this->tracker->fire($context);
    }
}
