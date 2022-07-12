<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Double;

use Chronhub\Messager\Message\DomainCommand;
use Chronhub\Messager\Message\PreValidationMessage;

final class SomeCommandToPreValidate extends DomainCommand implements PreValidationMessage
{
    public function validationRules(): array
    {
        return ['name' => 'required'];
    }
}
