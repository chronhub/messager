<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message\Serializer;

use Chronhub\Messager\Message\Content;
use Chronhub\Messager\Message\DomainEvent;
use Generator;
use Chronhub\Messager\Message\Message;

interface MessageSerializer
{
    public function serializeMessage(Message $message): array;

    /**
     * @param  array  $payload
     * @return Generator<DomainEvent|Content>
     */
    public function unserializeContent(array $payload): Generator;
}
