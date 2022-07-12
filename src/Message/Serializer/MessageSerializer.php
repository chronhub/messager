<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message\Serializer;

use Generator;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Message\DomainEvent;

interface MessageSerializer
{
    public function serializeMessage(Message $message): array;

    /**
     * @param  array  $payload
     * @return Generator<DomainEvent>
     */
    public function unserializeContent(array $payload): Generator;
}
