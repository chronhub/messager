<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Double;

use Chronhub\Messager\Message\AsyncMessage;
use Chronhub\Messager\Message\DomainCommand;

final class SomeAsyncCommand extends DomainCommand implements AsyncMessage
{
}
