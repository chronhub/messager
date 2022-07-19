<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message\Producer;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Chronhub\Messager\Message\Serializer\MessageSerializer;

final class IlluminateQueue implements MessageQueue
{
    public function __construct(private readonly QueueingDispatcher $queueingDispatcher,
                                private readonly MessageSerializer $messageSerializer,
                                private readonly ?string $connection = null,
                                private readonly ?string $queue = null)
    {
    }

    public function toQueue(Message $message): void
    {
        $messageJob = $this->toMessageJob($message);

        $this->queueingDispatcher->dispatchToQueue($messageJob);
    }

    private function toMessageJob(Message $message): object
    {
        $payload = $this->messageSerializer->serializeMessage($message);

        return new MessageJob(
            $payload,
            $message->header(Header::REPORTER_NAME->value),
            $this->connection,
            $this->queue
        );
    }
}
