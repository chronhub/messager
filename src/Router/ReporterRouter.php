<?php

declare(strict_types=1);

namespace Chronhub\Messager\Router;

use Closure;
use Illuminate\Support\Collection;
use Chronhub\Messager\Message\Message;
use Illuminate\Contracts\Container\Container;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Exceptions\ReporterException;
use Illuminate\Contracts\Container\BindingResolutionException;
use function is_string;
use function is_callable;
use function method_exists;

final class ReporterRouter implements Router
{
    public function __construct(private readonly array $map,
                                private readonly MessageAlias $messageAlias,
                                private readonly ?Container $container,
                                private readonly ?string $callableMethod)
    {
    }

    public function route(Message $message): Collection
    {
        return $this
            ->determineMessageHandler($message)
            ->transform(
                fn ($messageHandler): callable => $this->messageHandlerToCallable($messageHandler)
            );
    }

    private function messageHandlerToCallable(callable|object|string $messageHandler): callable
    {
        if (is_string($messageHandler)) {
            $messageHandler = $this->locateStringMessageHandler($messageHandler);
        }

        if (is_callable($messageHandler)) {
            return $messageHandler;
        }

        if ($this->callableMethod && method_exists($messageHandler, $this->callableMethod)) {
            return Closure::fromCallable([$messageHandler, $this->callableMethod]);
        }

        throw ReporterException::messageHandlerNotSupported();
    }

    private function determineMessageHandler(Message $message): Collection
    {
        $messageAlias = $this->messageAlias->instanceToAlias($message->event());

        if (null === $messageHandlers = $this->map[$messageAlias] ?? null) {
            throw ReporterException::messageNameNotFound($messageAlias);
        }

        return (new Collection())->wrap($messageHandlers);
    }

    /**
     * @throws BindingResolutionException
     */
    private function locateStringMessageHandler(string $messageHandler): object
    {
        if (! $this->container) {
            throw ReporterException::missingContainer($messageHandler);
        }

        return $this->container->make($messageHandler);
    }
}
