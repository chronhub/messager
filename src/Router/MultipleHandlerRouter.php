<?php

declare(strict_types=1);

namespace Chronhub\Messager\Router;

use Illuminate\Support\Collection;
use Chronhub\Messager\Message\Message;

final class MultipleHandlerRouter implements Router
{
    public function __construct(private readonly Router $router)
    {
    }

    public function route(Message $message): Collection
    {
        return $this->router->route($message);
    }
}
