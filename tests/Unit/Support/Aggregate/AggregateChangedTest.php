<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Support\Aggregate;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeAggregateChanged;
use Chronhub\Messager\Support\UniqueIdentifier\GenerateUuidV4;

final class AggregateChangedTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $aggregateId = (new GenerateUuidV4())->generate();

        $event = SomeAggregateChanged::occur($aggregateId, ['name' => 'steph']);

        $this->assertEquals($aggregateId, $event->aggregateId());
        $this->assertEquals(['name' => 'steph'], $event->toContent());
    }
}
