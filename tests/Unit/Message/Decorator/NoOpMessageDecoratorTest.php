<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\NoOpMessageDecorator;

final class NoOpMessageDecoratorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_return_message_as_is(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $sameMessage = (new NoOpMessageDecorator())->decorate($message);

        $this->assertEquals($message, $sameMessage);
    }
}
