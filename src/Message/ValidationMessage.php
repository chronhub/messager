<?php

declare(strict_types=1);

namespace Chronhub\Messager\Message;

interface ValidationMessage extends Messaging
{
    public function validationRules(): array;
}
