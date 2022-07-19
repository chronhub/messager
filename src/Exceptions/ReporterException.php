<?php

declare(strict_types=1);

namespace Chronhub\Messager\Exceptions;

use Chronhub\Messager\Reporter;

class ReporterException extends MessagingException
{
    public static function messageHandlerNotSupported(): self
    {
        return new self('Message handler type not supported');
    }

    public static function oneMessageHandlerOnly(): self
    {
        return new self('Router require one message handler only');
    }

    public static function messageNameNotFound(string $messageName): self
    {
        return new self("Message name $messageName not found in map");
    }

    public static function missingContainer(string $messageHandler): self
    {
        return new self("Container is required for string message handler $messageHandler");
    }

    public static function missingAsyncMarkerHeader(string $eventType): self
    {
        return new self("Missing async marker for event $eventType");
    }

    public static function invalidMessagerConfiguration(string $driver, string $type): self
    {
        return new self("Invalid messager configuration with $driver driver and $type type");
    }

    public static function invalidMessagerType(string $type): self
    {
        return new self("Invalid messager type $type");
    }

    public static function invalidReporterInstance(string $reporterClass): self
    {
        $message = "Invalid messager class name $reporterClass, ";
        $message .= 'Must be an instance of '.Reporter::class;

        return new self($message);
    }

    public static function invalidMessageProducerConfiguration(string $strategy): self
    {
        return new self("Invalid message producer config for strategy $strategy");
    }
}
