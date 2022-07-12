<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\MarkEventType;

final class MarkEventTypeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_set_event_type_header(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $decorator = new MarkEventType();

        $messageMarked = $decorator->decorate($message);

        $this->assertNull($message->header(Header::EVENT_TYPE->value));
        $this->assertEquals([Header::EVENT_TYPE->value => SomeCommand::class], $messageMarked->headers());
    }

    /**
     * @test
     */
    public function it_does_not_override_event_type_header_if_already_exists(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']), [
            Header::EVENT_TYPE->value => 'some-command',
        ]);

        $decorator = new MarkEventType();

        $messageMarked = $decorator->decorate($message);

        $this->assertEquals([Header::EVENT_TYPE->value => 'some-command'], $messageMarked->headers());
    }
}
