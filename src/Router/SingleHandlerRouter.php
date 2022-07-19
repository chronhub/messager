<?php

declare(strict_types=1);

namespace Chronhub\Messager\Router;

use Illuminate\Support\Collection;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Exceptions\ReportingMessageFailed;

final class SingleHandlerRouter implements Router
{
    public function __construct(private readonly Router $router)
    {
    }

    public function route(Message $message): Collection
    {
        $messageHandlers = $this->router->route($message);

        if (1 !== $messageHandlers->count()) {
            throw ReportingMessageFailed::oneMessageHandlerOnly();
        }

        return $messageHandlers;
    }
}
