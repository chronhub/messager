<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Serializer;

use stdClass;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Exceptions\RuntimeException;
use Chronhub\Messager\Tests\Double\SomeAggregateChanged;
use Chronhub\Messager\Message\Serializer\GenericContentSerializer;

final class GenericContentSerializerTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_serialize_event_content(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $serializer = new GenericContentSerializer();

        $this->assertEquals(['name' => 'steph'], $serializer->serialize($event));
    }

    /**
     * @test
     */
    public function it_unserialize_content_from_event_content(): void
    {
        $payload = [
            'headers' => ['some' => 'header'],
            'content' => ['name' => 'steph'],
        ];

        $serializer = new GenericContentSerializer();

        $event = $serializer->unserialize(SomeCommand::class, $payload);

        $this->assertInstanceOf(SomeCommand::class, $event);
        $this->assertEmpty($event->headers());
    }

    /**
     * @test
     */
    public function it_unserialize_content_from_aggregate_changed_event(): void
    {
        $payload = [
            'headers' => [Header::AGGREGATE_ID->value => '123-456'],
            'content' => ['name' => 'steph'],
        ];

        $serializer = new GenericContentSerializer();

        $event = $serializer->unserialize(SomeAggregateChanged::class, $payload);

        $this->assertInstanceOf(SomeAggregateChanged::class, $event);
        $this->assertEmpty($event->headers());

        $this->assertEquals('123-456', $event->aggregateId());
    }

    /**
     * @test
     */
    public function it_raise_exception_when_unserialize_an_invalid_event_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Invalid source');

        (new GenericContentSerializer())->unserialize(stdClass::class, ['some' => 'content']);
    }
}
