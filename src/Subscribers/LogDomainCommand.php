<?php

declare(strict_types=1);

namespace Chronhub\Messager\Subscribers;

use Psr\Log\LoggerInterface;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\OnDispatchPriority;
use Chronhub\Messager\Tracker\MessageTracker;
use Chronhub\Messager\Tracker\ContextualMessage;
use Chronhub\Messager\Message\Serializer\MessageSerializer;
use function is_array;

final class LogDomainCommand extends AbstractMessageSubscriber
{
    public function __construct(private LoggerInterface $logger,
                                private MessageSerializer $messageSerializer)
    {
    }

    public function attachToTracker(MessageTracker $tracker): void
    {
        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $context->transientMessage();

            if (! is_array($message)) {
                return;
            }

            $this->logger->debug('On dispatch to message factory', [
                'context' => [
                    'message_name' => $this->determineMessageName($message),
                    'message'      => $message,
                ],
            ]);
        }, OnDispatchPriority::MESSAGE_FACTORY->value + 1);

        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $this->logger->debug('On dispatch after route', [
                'context' => [
                    'message_name' => $this->determineMessageName($context->message()),
                    'async_marker' => $context->message()->header(Header::ASYNC_MARKER->value),
                    'exception'    => $context->exception(),
                ],
            ]);
        }, OnDispatchPriority::ROUTE->value - 1);

        $this->listeners[] = $tracker->listen(Reporter::DISPATCH_EVENT, function (ContextualMessage $context): void {
            $message = $context->message();

            $serializedMessage = $message->isMessaging()
                ? $this->messageSerializer->serializeMessage($message)
                : serialize($message->event());

            $this->logger->debug('On dispatch before invoke message handler', [
                'context' => [
                    'message_name'         => $this->determineMessageName($message),
                    'has_message_handlers' => iterator_count($context->messageHandlers()) > 0,
                    'exception'            => $context->exception(),
                    'message'              => $serializedMessage,
                ],
            ]);
        }, OnDispatchPriority::INVOKE_HANDLER->value + 1);

        $this->listeners[] = $tracker->listen(Reporter::FINALIZE_EVENT, function (ContextualMessage $context): void {
            $this->logger->debug('On pre finalize message command', [
                'context' => [
                    'message_name'    => $this->determineMessageName($context->message()),
                    'message_handled' => $context->isMessageHandled(),
                    'async_marker' => $context->message()->header(Header::ASYNC_MARKER->value),
                    'exception'       => $context->exception(),
                ],
            ]);
        }, 100000);

        $this->listeners[] = $tracker->listen(Reporter::FINALIZE_EVENT, function (ContextualMessage $context): void {
            $this->logger->debug('On post finalize message command', [
                'context' => [
                    'message_name'    => $this->determineMessageName($context->message()),
                    'message_handled' => $context->isMessageHandled(),
                    'async_marker' => $context->message()->header(Header::ASYNC_MARKER->value),
                    'exception'       => $context->exception(),
                ],
            ]);
        }, -100000);
    }

    private function determineMessageName(Message|array $message): string
    {
        if ($message instanceof Message) {
            $eventType = $message->header(Header::EVENT_TYPE->value);

            return $eventType ?? $message->event()::class;
        }

        return $message['headers'][Header::EVENT_TYPE->value] ?? $message['message_name'] ?? 'Undetermined event type';
    }
}
