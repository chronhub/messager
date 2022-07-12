<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Double;

use Chronhub\Messager\Message\Domain;

final class SomeDomain extends Domain
{
    public function type(): string
    {
        return 'domain_test';
    }
}
