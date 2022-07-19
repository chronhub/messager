<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Router;

use Illuminate\Support\Collection;
use stdclass;
use Generator;
use Chronhub\Messager\Router\Router;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Router\SingleHandlerRouter;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Exceptions\ReportingMessageFailed;

final class SingleHandlerRouterTest extends TestCaseWithProphecy
{
    private ObjectProphecy|Router $router;

    public function setUp(): void
    {
        parent::setUp();

        $this->router = $this->prophesize(Router::class);
    }

    /**
     * @test
     */
    public function it_route_message_to_one_handler_only(): void
    {
        $message = new Message(new stdclass());

        $expectedMessageHandlers = new Collection([function (): void {}]);

        $this->router->route($message)->willReturn($expectedMessageHandlers)->shouldBeCalled();

        $router = new SingleHandlerRouter($this->router->reveal());

        $messageHandlers = $router->route($message);

        $this->assertEquals($expectedMessageHandlers, $messageHandlers);
    }

    /**
     * @test
     * @dataProvider provideInvalidCountMessageHandlers
     */
    public function it_raise_exception_with_invalid_count_message_handlers(array $messageHandlers): void
    {
        $this->expectException(ReportingMessageFailed::class);
        $this->expectExceptionMessage('Router require one message handler only');

        $message = new Message(new stdclass());

        $this->router->route($message)->willReturn(new Collection($messageHandlers))->shouldBeCalled();

        $router = new SingleHandlerRouter($this->router->reveal());
        $router->route($message);
    }

    public function provideInvalidCountMessageHandlers(): Generator
    {
        yield [[]];

        yield [[
            function (): void {},
            function (): void {},
        ]];
    }
}
