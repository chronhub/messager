<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeEvent;

final class DomainEventTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_event_type(): void
    {
        $event = SomeEvent::fromContent([]);

        $this->assertEquals('event', $event->type());
    }
}
