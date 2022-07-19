<?php

declare(strict_types=1);

namespace Chronhub\Messager;

use React\Promise\PromiseInterface;
use Chronhub\Messager\Subscribers\MessageSubscriber;

interface Reporter
{
    public const DISPATCH_EVENT = 'dispatch_event';

    public const FINALIZE_EVENT = 'finalize_event';

    /**
     * @param  object|array          $message
     * @return void|PromiseInterface
     */
    public function publish(object|array $message);

    public function subscribe(MessageSubscriber ...$messageSubscribers): void;

    /**
     * @return string fully qualified class name or service id
     */
    public function name(): string;
}
