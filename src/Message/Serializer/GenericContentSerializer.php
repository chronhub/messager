<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message\Serializer;

use Chronhub\Messager\Message\Domain;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Exceptions\RuntimeException;
use Chronhub\Messager\Support\Aggregate\AggregateChanged;
use function is_subclass_of;

final class GenericContentSerializer
{
    public function serialize(Domain $event): array
    {
        return $event->toContent();
    }

    public function unserialize(string $source, array $payload): Domain
    {
        if (is_subclass_of($source, AggregateChanged::class)) {
            $aggregateId = $payload['headers'][Header::AGGREGATE_ID->value];

            return $source::occur($aggregateId, $payload['content']);
        }

        if (is_subclass_of($source, Domain::class)) {
            return $source::fromContent($payload['content']);
        }

        throw new RuntimeException('Invalid source');
    }
}
