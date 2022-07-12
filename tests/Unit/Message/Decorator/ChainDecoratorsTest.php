<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\MarkEventId;
use Chronhub\Messager\Message\Decorator\MarkEventTime;
use Chronhub\Messager\Message\Decorator\MarkEventType;
use Chronhub\Messager\Support\Clock\UniversalSystemClock;
use Chronhub\Messager\Message\Decorator\ChainMessageDecorators;

final class ChainDecoratorsTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_chain_message_decorators(): void
    {
        $decorators = [
            new MarkEventId(),
            new MarkEventTime(new UniversalSystemClock()),
            new MarkEventType(),
        ];

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']), []);

        $this->assertEquals([], $message->headers());
        $this->assertEquals([], $message->event()->headers());

        $chain = new ChainMessageDecorators(...$decorators);
        $messageMarked = $chain->decorate($message);

        $this->assertNotEmpty($messageMarked->headers());
        $this->assertTrue($messageMarked->has(Header::EVENT_ID->value));
        $this->assertTrue($messageMarked->has(Header::EVENT_TIME->value));
        $this->assertTrue($messageMarked->has(Header::EVENT_TYPE->value));

        $this->assertNotEquals($message, $messageMarked);
    }
}
