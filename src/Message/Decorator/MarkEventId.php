<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message\Decorator;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Support\UniqueIdentifier\UuidGenerator;
use Chronhub\Messager\Support\UniqueIdentifier\GenerateUuidV4;

final class MarkEventId implements MessageDecorator
{
    public function __construct(private ?UuidGenerator $uuid = null)
    {
        $this->uuid = $uuid ?? new GenerateUuidV4();
    }

    public function decorate(Message $message): Message
    {
        if ($message->hasNot(Header::EVENT_ID->value)) {
            $message = $message->withHeader(Header::EVENT_ID->value, $this->uuid->generate());
        }

        return $message;
    }
}
