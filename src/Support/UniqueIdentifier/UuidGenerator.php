<?php

declare(strict_types=1);

namespace Chronhub\Messager\Support\UniqueIdentifier;

interface UuidGenerator
{
    public function generate(): string;
}
