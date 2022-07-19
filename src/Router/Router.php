<?php

declare(strict_types=1);

namespace Chronhub\Messager\Router;

use Illuminate\Support\Collection;
use Chronhub\Messager\Message\Message;

interface Router
{
    public function route(Message $message): Collection;
}
