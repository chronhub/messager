<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeQuery;

final class DomainQueryTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_event_type(): void
    {
        $event = SomeQuery::fromContent([]);

        $this->assertEquals('query', $event->type());
    }
}
