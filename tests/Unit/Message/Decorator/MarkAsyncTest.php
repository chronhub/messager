<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use stdClass;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\MarkAsync;

final class MarkAsyncTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_set_event_async_marker_header(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $decorator = new MarkAsync();

        $messageMarked = $decorator->decorate($message);

        $this->assertNull($message->header(Header::ASYNC_MARKER->value));
        $this->assertFalse($messageMarked->header(Header::ASYNC_MARKER->value));
    }

    /**
     * @test
     */
    public function it_does_not_override_event_async_marker_header_if_already_exists(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']), [
            Header::ASYNC_MARKER->value => true,
        ]);

        $decorator = new MarkAsync();

        $messageMarked = $decorator->decorate($message);

        $this->assertTrue($message->header(Header::ASYNC_MARKER->value));
        $this->assertTrue($messageMarked->header(Header::ASYNC_MARKER->value));
    }

    /**
     * @test
     */
    public function it_does_not_mark_async_marker_header_with_no_messaging_event(): void
    {
        $message = new Message(new stdClass());

        $decorator = new MarkAsync();

        $messageMarked = $decorator->decorate($message);

        $this->assertEquals($message, $messageMarked);
        $this->assertFalse($messageMarked->has(Header::ASYNC_MARKER->value));
    }
}
