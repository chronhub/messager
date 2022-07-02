<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Temp;

use Throwable;
use Chronhub\Messager\ReportEvent;
use Chronhub\Messager\Tests\TestCase;
use Chronhub\Messager\Message\DomainEvent;
use Chronhub\Messager\Router\ReporterRouter;
use Chronhub\Messager\Tests\Double\SomeEvent;
use Chronhub\Messager\Subscribers\HandleEvent;
use Chronhub\Messager\Subscribers\MakeMessage;
use Chronhub\Messager\Subscribers\HandleRouter;
use Chronhub\Messager\Router\MultipleHandlerRouter;
use Chronhub\Messager\Subscribers\NameReporterService;
use Chronhub\Messager\Message\Alias\AliasFromInflector;
use Chronhub\Messager\Support\Clock\UniversalSystemClock;
use Chronhub\Messager\Message\Producer\SyncMessageProducer;
use Chronhub\Messager\Message\Factory\GenericMessageFactory;
use Chronhub\Messager\Support\UniqueIdentifier\GenerateUuidV4;
use Chronhub\Messager\Message\Decorator\DefaultMessageDecorators;
use Chronhub\Messager\Message\Serializer\GenericMessageSerializer;
use Chronhub\Messager\Subscribers\ChainMessageDecoratorSubscriber;

final class DispatchEventTest extends TestCase
{
    /**
     * @test
     *
     * @return void
     *
     * @throws Throwable
     */
    public function it_dispatch_event(): void
    {
        $someEventHandled = false;
        $sameEventHandled = false;

        $someEvent = null;
        $sameEvent = null;

        $map = [
            'some-event' => [
                function (DomainEvent $event) use (&$someEventHandled, &$someEvent): void {
                    $someEvent = $event;
                    $someEventHandled = true;
                },
                function (DomainEvent $event) use (&$sameEventHandled, &$sameEvent): void {
                    $sameEvent = $event;
                    $sameEventHandled = true;
                },
            ],
        ];

        $reporter = new ReportEvent('report.event');

        $reporter->subscribe(
            new NameReporterService($reporter->name()),
            new MakeMessage(new GenericMessageFactory(
                    new GenericMessageSerializer(new UniversalSystemClock(), new GenerateUuidV4()))
            ),
            new ChainMessageDecoratorSubscriber(new DefaultMessageDecorators()),
            new HandleRouter(
                new MultipleHandlerRouter(
                    new ReporterRouter($map, new AliasFromInflector(), null, null)
                ),
                new SyncMessageProducer()
            ),
            new HandleEvent(),
        );

        $event = SomeEvent::fromContent(['steph' => 'bug']);
        $reporter->publish($event);

        $this->assertTrue($someEventHandled);
        $this->assertTrue($sameEventHandled);
        $this->assertEquals($someEvent, $sameEvent);
    }
}
