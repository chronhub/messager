<?php

declare(strict_types=1);

namespace Chronhub\Messager\Support\UniqueIdentifier;

use Ramsey\Uuid\Uuid;

final class GenerateUuidV4 implements UuidGenerator
{
    public function generate(): string
    {
        return Uuid::uuid4()->toString();
    }
}
