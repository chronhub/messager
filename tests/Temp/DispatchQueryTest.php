<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Temp;

use Throwable;
use React\Promise\Deferred;
use Chronhub\Messager\ReportQuery;
use React\Promise\PromiseInterface;
use Chronhub\Messager\Tests\TestCase;
use Chronhub\Messager\Router\ReporterRouter;
use Chronhub\Messager\Support\HandlePromise;
use Chronhub\Messager\Tests\Double\SomeQuery;
use Chronhub\Messager\Subscribers\HandleQuery;
use Chronhub\Messager\Subscribers\MakeMessage;
use Chronhub\Messager\Subscribers\HandleRouter;
use Chronhub\Messager\Router\SingleHandlerRouter;
use Chronhub\Messager\Subscribers\NameReporterService;
use Chronhub\Messager\Message\Alias\AliasFromInflector;
use Chronhub\Messager\Support\Clock\UniversalSystemClock;
use Chronhub\Messager\Message\Producer\SyncMessageProducer;
use Chronhub\Messager\Message\Factory\GenericMessageFactory;
use Chronhub\Messager\Message\Decorator\DefaultMessageDecorators;
use Chronhub\Messager\Message\Serializer\GenericMessageSerializer;
use Chronhub\Messager\Subscribers\ChainMessageDecoratorSubscriber;

final class DispatchQueryTest extends TestCase
{
    use HandlePromise;

    /**
     * @test
     *
     * @return void
     *
     * @throws Throwable
     */
    public function it_dispatch_query(): void
    {
        $messageHandled = false;
        $someQuery = null;

        $map = [
            'some-query' => function (SomeQuery $query, Deferred $promise) use (&$messageHandled, &$someQuery): void {
                $someQuery = $query;
                $messageHandled = true;

                $promise->resolve($query->toContent()['name']);
            },
        ];

        $reporter = new ReportQuery('report.query');

        $reporter->subscribe(
            new NameReporterService($reporter->name()),
            new MakeMessage(new GenericMessageFactory(new GenericMessageSerializer(new UniversalSystemClock()))),
            new ChainMessageDecoratorSubscriber(new DefaultMessageDecorators()),
            new HandleRouter(
                new SingleHandlerRouter(
                    new ReporterRouter($map, new AliasFromInflector(), null, null)
                ),
                new SyncMessageProducer()
            ),
            new HandleQuery(),
        );

        $event = SomeQuery::fromContent(['name' => 'steph bug']);
        $promise = $reporter->publish($event);

        $this->assertTrue($messageHandled);

        $this->assertInstanceOf(PromiseInterface::class, $promise);
        $this->assertEquals('steph bug', $this->handlePromise($promise));

        dump($someQuery);
    }
}
