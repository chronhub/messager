<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Router;

use Illuminate\Container\Container;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Router\ReporterRouter;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Alias\MessageAlias;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Exceptions\ReporterException;

final class RouterTest extends TestCaseWithProphecy
{
    private ObjectProphecy|MessageAlias $alias;

    protected function setUp(): void
    {
        parent::setUp();

        $this->alias = $this->prophesize(MessageAlias::class);
    }

    /**
     * @test
     */
    public function it_route_message_to_his_handlers(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = [
            'some-command' => function (): void {},
        ];

        $router = new ReporterRouter($map, $this->alias->reveal(), null, null);

        $this->assertEquals(function (): void {}, $router->route($message)[0]);
    }

    /**
     * @test
     */
    public function it_locate_string_message_handler_from_container(): void
    {
        $container = new Container();
        $container->bind('message_handler', function () {
            return function (): void {};
        });

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = ['some-command' => 'message_handler'];

        $router = new ReporterRouter($map, $this->alias->reveal(), $container, null);

        $this->assertEquals(function (): void {
        }, $router->route($message)[0]);
    }

    /**
     * @test
     */
    public function it_transform_non_callable_handler_to_callable_with_method_name(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = ['some-command' => new class()
        {
            public function command(SomeCommand $command): string
            {
                return $command->toContent()['name'];
            }
        }, ];

        $router = new ReporterRouter($map, $this->alias->reveal(), null, 'command');

        $messageHandler = $router->route($message)[0];

        $this->assertEquals('steph', $messageHandler($message->event()));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_name_not_found_in_map(): void
    {
        $this->expectException(ReporterException::class);
        $this->expectExceptionMessage('Message name some-command not found in map');

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = [];

        $router = new ReporterRouter($map, $this->alias->reveal(), null, null);

        $router->route($message)[0];
    }

    /**
     * @test
     */
    public function it_raise_exception_if_message_handler_is_not_callable(): void
    {
        $this->expectException(ReporterException::class);
        $this->expectExceptionMessage('Message handler type not supported');

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = ['some-command' => new class()
        {
            public function command(SomeCommand $command): string
            {
                return $command->toContent()['name'];
            }
        }, ];

        $router = new ReporterRouter($map, $this->alias->reveal(), null, null);

        $messageHandler = $router->route($message)[0];

        $this->assertEquals('steph', $messageHandler($message->event()));
    }

    /**
     * @test
     */
    public function it_raise_exception_if_string_message_handler_and_no_container(): void
    {
        $this->expectException(ReporterException::class);
        $this->expectExceptionMessage('Container is required for string message handler message_handler_not_found');

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $this->alias->instanceToAlias($message->event())->willReturn('some-command')->shouldBeCalled();

        $map = ['some-command' => 'message_handler_not_found'];

        $router = new ReporterRouter($map, $this->alias->reveal(), null, null);

        $router->route($message);
    }
}
