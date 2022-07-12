<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Double;

use Chronhub\Messager\Message\DomainCommand;
use Chronhub\Messager\Message\ValidationMessage;

final class SomeCommandToValidate extends DomainCommand implements ValidationMessage
{
    public function validationRules(): array
    {
        return ['name' => 'required'];
    }
}
