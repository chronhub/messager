<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\MarkEventId;
use Chronhub\Messager\Support\UniqueIdentifier\GenerateUuidV4;

final class MarkEventIdTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_set_event_id_header(): void
    {
        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $decorator = new MarkEventId();

        $messageMarked = $decorator->decorate($message);

        $this->assertNull($message->header(Header::EVENT_ID->value));
        $this->assertIsString($messageMarked->header(Header::EVENT_ID->value));
    }

    /**
     * @test
     */
    public function it_does_not_override_event_id_header_if_already_exists(): void
    {
        $eventId = (new GenerateUuidV4())->generate();

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']), [
            Header::EVENT_ID->value => $eventId,
        ]);

        $decorator = new MarkEventId();

        $messageMarked = $decorator->decorate($message);

        $this->assertEquals([Header::EVENT_ID->value => $eventId], $messageMarked->headers());
    }
}
