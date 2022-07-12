<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Router;

use stdclass;
use Chronhub\Messager\Router\Router;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Router\MultipleHandlerRouter;

final class MultipleHandlerRouterTest extends TestCaseWithProphecy
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
    public function it_route_message_to_multiple_message_handlers(): void
    {
        $message = new Message(new stdclass());

        $expectedMessageHandlers = [
            function (): void {},
            function (): void {},
        ];

        $this->router->route($message)->willReturn($expectedMessageHandlers)->shouldBeCalled();

        $router = new MultipleHandlerRouter($this->router->reveal());

        $messageHandlers = $router->route($message);

        $this->assertEquals($expectedMessageHandlers, $messageHandlers);
    }

    /**
     * @test
     */
    public function it_route_message_to_empty_message_handlers(): void
    {
        $message = new Message(new stdclass());

        $expectedMessageHandlers = [];

        $this->router->route($message)->willReturn($expectedMessageHandlers)->shouldBeCalled();

        $router = new MultipleHandlerRouter($this->router->reveal());

        $messageHandlers = $router->route($message);

        $this->assertEquals($expectedMessageHandlers, $messageHandlers);
    }
}
