<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Domain;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\OnDispatchPriority;
use Illuminate\Contracts\Validation\Factory;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Message\ValidationMessage;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\PreValidationMessage;
use Chronhub\Messager\Exceptions\ReportingMessageFailed;
use Chronhub\Messager\Exceptions\ValidationMessageFailed;

final class ValidateCommand extends AbstractMessageSubscriber
{
    public function __construct(private Factory $validator)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $context->message();

            if (! $message->isMessaging()) {
                return;
            }

            $this->validateEventIfRequired($message);
        }, OnDispatchPriority::MESSAGE_VALIDATION->value);
    }

    private function validateEventIfRequired(Message $message): void
    {
        $event = $message->event();

        if (! $event instanceof ValidationMessage) {
            return;
        }

        $alreadyProducedAsync = $event->header(Header::ASYNC_MARKER->value);

        if (null === $alreadyProducedAsync) {
            throw ReportingMessageFailed::missingAsyncMarkerHeader($message->header(Header::EVENT_TYPE->value));
        }

        if ($event instanceof PreValidationMessage && $alreadyProducedAsync) {
            return;
        }

        if ($event instanceof PreValidationMessage && ! $alreadyProducedAsync) {
            $this->validateMessage($message);
        }

        if ($alreadyProducedAsync) {
            $this->validateMessage($message);
        }
    }

    private function validateMessage(Message $message): void
    {
        /** @var ValidationMessage|Domain $event */
        $event = $message->event();

        $validator = $this->validator->make($event->toContent(), $event->validationRules());

        if ($validator->fails()) {
            throw ValidationMessageFailed::withValidator($validator, $message);
        }
    }
}
