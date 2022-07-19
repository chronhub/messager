<?php

declare(strict_types=1);

namespace Chronhub\Messager;

use Illuminate\Support\Arr;
use Chronhub\Messager\Message\DomainType;
use Illuminate\Contracts\Config\Repository;
use Chronhub\Messager\Router\ReporterRouter;
use Illuminate\Contracts\Container\Container;
use Chronhub\Messager\Subscribers\HandleRouter;
use Illuminate\Contracts\Bus\QueueingDispatcher;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Router\SingleHandlerRouter;
use Chronhub\Messager\Exceptions\ReporterException;
use Chronhub\Messager\Router\MultipleHandlerRouter;
use Chronhub\Messager\Subscribers\MessageSubscriber;
use Chronhub\Messager\Subscribers\NameReporterService;
use Chronhub\Messager\Message\Producer\IlluminateQueue;
use Chronhub\Messager\Message\Producer\MessageProducer;
use Chronhub\Messager\Message\Producer\SyncMessageProducer;
use Chronhub\Messager\Message\Serializer\MessageSerializer;
use Chronhub\Messager\Message\Decorator\ChainMessageDecorators;
use Chronhub\Messager\Subscribers\ChainMessageDecoratorSubscriber;
use function is_array;
use function array_map;
use function is_string;
use function is_subclass_of;

final class DefaultMessagerManager implements MessagerManager
{
    /**
     * @var array<string,callable>
     */
    private array $customMessager = [];

    public function __construct(private readonly Container $container,
                                private ?array $config = null)
    {
        $this->config ??= $container->get(Repository::class)->get('messager');
    }

    public function create(string $driver, string $type): Reporter
    {
        $messagerKey = $this->determineMessagerKey($driver, $type);

        if ($customMessager = ($this->customMessager[$messagerKey] ?? null)) {
            return $customMessager($this->container, $this->config);
        }

        $config = $this->fromMessager("reporting.$type.$driver");

        if (! is_array($config) || empty($config)) {
            throw ReporterException::invalidMessagerConfiguration($driver, $type);
        }

        return $this->createMessager($type, $config);
    }

    public function command(string $driver = 'default'): Reporter
    {
        return $this->create($driver, DomainType::COMMAND->value);
    }

    public function event(string $driver = 'default'): Reporter
    {
        return $this->create($driver, DomainType::EVENT->value);
    }

    public function query(string $driver = 'default'): Reporter
    {
        return $this->create($driver, DomainType::QUERY->value);
    }

    public function extends(string $driver, string $type, callable $messager): void
    {
        $messagerKey = $this->determineMessagerKey($driver, $type);

        $this->customMessager[$messagerKey] = $messager;
    }

    private function createMessager(string $type, array $config): Reporter
    {
        $messager = $this->messagerInstance($type, $config);

        $this->subscribeToMessager($messager, $type, $config);

        return $messager;
    }

    private function subscribeToMessager(Reporter $reporter, string $type, array $config): void
    {
        $subscribers = $this->resolveServices([
            new NameReporterService($reporter->name()),
            $this->fromMessager('messaging.subscribers'),
            $config['messaging']['subscribers'] ?? [],
            $this->chainMessageDecoratorsSubscribers($config),
            $this->messagerRouterSubscriber($type, $config),
        ]);

        $reporter->subscribe(...$subscribers);
    }

    private function messagerRouterSubscriber(string $type, array $config): MessageSubscriber
    {
        $useContainer = $config['use_container'] ?? true;

        $router = new ReporterRouter(
            $config['map'],
            $this->container->get(MessageAlias::class),
            $useContainer ? $this->container : null,
            $config['handler_method'] ?? null
        );

        $messagerRouter = match ($type) {
            'command', 'query' => new SingleHandlerRouter($router),
            'event' => new MultipleHandlerRouter($router),
            default => throw ReporterException::invalidMessagerType($type)
        };

        $messageProducer = $this->createMessageProducer($type, $config['messaging']['producer'] ?? null);

        return new HandleRouter($messagerRouter, $messageProducer);
    }

    private function chainMessageDecoratorsSubscribers(array $config): MessageSubscriber
    {
        $messageDecorators = $this->resolveServices(
            $this->fromMessager('messaging.decorators'),
            $config['messaging']['decorators'] ?? []
        );

        return new ChainMessageDecoratorSubscriber(
            new ChainMessageDecorators(...$messageDecorators)
        );
    }

    private function resolveServices(array ...$services): array
    {
        return array_map(function ($service) {
            return is_string($service) ? $this->container->make($service) : $service;
        }, Arr::flatten($services));
    }

    private function messagerInstance(string $type, array $config): Reporter
    {
        if (null === $concrete = ($config['concrete'] ?? null)) {
            $concrete = match ($type) {
                'command' => ReportCommand::class,
                'event'   => ReportEvent::class,
                'query'   => ReportQuery::class,
                default   => throw ReporterException::invalidMessagerType($type)
            };
        }

        if (! is_subclass_of($concrete, Reporter::class)) {
            throw ReporterException::invalidReporterInstance($concrete);
        }

        if (is_string($tracker = $config['tracker_id'] ?? null)) {
            $tracker = $this->container->get($tracker);
        }

        return new $concrete($config['service_id'] ?? $concrete, $tracker);
    }

    private function createMessageProducer(string $type, ?string $strategy): MessageProducer
    {
        if (null === $strategy || 'default' === $strategy) {
            $strategy = $this->fromMessager('messaging.producer.default');
        }

        if (DomainType::QUERY->value === $type || 'sync' === $strategy) {
            return new SyncMessageProducer();
        }

        $config = $this->fromMessager("messaging.producer.$strategy");

        if (! is_array($config) || empty($config)) {
            throw ReporterException::invalidMessageProducerConfiguration($strategy);
        }

        $producer = $config['service'];

        if ($this->container->bound($producer)) {
            return $this->container->get($producer);
        }

        if (null === $queue = $config['queue'] ?? null) {
            $queue = $this->container->make(IlluminateQueue::class);
        }

        if (is_array($queue)) {
            $queue = new IlluminateQueue(
                $this->container->get(QueueingDispatcher::class),
                $this->container->get(MessageSerializer::class),
                $queue['connection'] ?? null,
                $queue['queue'] ?? null,
            );
        }

        return new $producer(
            is_string($queue) ? $this->container->make($queue) : $queue
        );
    }

    private function determineMessagerKey(string $driver, string $type): string
    {
        if (null === DomainType::tryFrom($type)) {
            throw ReporterException::invalidMessagerType('invalid type');
        }

        return $type.':'.$driver;
    }

    private function fromMessager(string $key): mixed
    {
        return Arr::get($this->config, $key);
    }
}
