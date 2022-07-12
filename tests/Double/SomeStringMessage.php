<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Double;

final class SomeStringMessage
{
    public function __construct(private string $text)
    {
    }

    public function getText(): string
    {
        return $this->text;
    }
}
