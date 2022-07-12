<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Decorator;

use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Decorator\MarkEventTime;
use Chronhub\Messager\Support\Clock\UniversalSystemClock;

final class MarkEventTimeTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_set_event_time_header(): void
    {
        $clock = new UniversalSystemClock();

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']));

        $decorator = new MarkEventTime($clock);

        $messageMarked = $decorator->decorate($message);

        $this->assertNull($message->header(Header::EVENT_TIME->value));
        $this->assertIsString($messageMarked->header(Header::EVENT_TIME->value));
    }

    /**
     * @test
     */
    public function it_does_not_override_event_time_header_if_already_exists(): void
    {
        $clock = new UniversalSystemClock();
        $now = $clock->fromNow()->toString();

        $message = new Message(SomeCommand::fromContent(['name' => 'steph']), [
            Header::EVENT_TIME->value => $now,
        ]);

        $decorator = new MarkEventTime($clock);

        $messageMarked = $decorator->decorate($message);

        $this->assertEquals([Header::EVENT_TIME->value => $now], $messageMarked->headers());
    }
}
