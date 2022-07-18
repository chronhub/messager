<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Serializer;

use stdclass;
use Generator;
use Ramsey\Uuid\Uuid;
use Chronhub\Messager\Message\Header;
use Prophecy\Prophecy\ObjectProphecy;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Support\Clock\Clock;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Support\Clock\PointInTime;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Exceptions\RuntimeException;
use Chronhub\Messager\Tests\Double\SomeAggregateChanged;
use Chronhub\Messager\Support\Clock\UniversalPointInTime;
use Chronhub\Messager\Support\UniqueIdentifier\GenerateUuidV4;
use Chronhub\Messager\Message\Serializer\GenericMessageSerializer;

final class GenericMessageSerializerTest extends TestCaseWithProphecy
{
    private ObjectProphecy|Clock $clock;

    public function setUp(): void
    {
        parent::setUp();

        $this->clock = $this->prophesize(Clock::class);
    }

    /**
     * @test
     */
    public function it_serialize_message(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);
        $headers = [
            Header::EVENT_TYPE->value => $eventClass = SomeCommand::class,
            Header::EVENT_ID->value   => $uid = (new GenerateUuidV4())->generate(),
            Header::EVENT_TIME->value => $time = UniversalPointInTime::now()->toString(),
        ];

        $message = new Message($event, $headers);

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $serializedEvent = $serializer->serializeMessage($message);

        $payload =
            [
                'headers' => [
                    Header::EVENT_TYPE->value => $eventClass,
                    Header::EVENT_ID->value   => $uid,
                    Header::EVENT_TIME->value => $time,
                ],
                'content' => ['name' => 'steph'],
            ];

        $this->assertEquals($payload, $serializedEvent);
    }

    /**
     * @test
     */
    public function it_serialize_message_and_provide_missing_default_headers(): void
    {
        $pointInTime = $this->prophesize(PointInTime::class);
        $pointInTime->toString()->willReturn('some_date_time')->shouldBeCalled();
        $this->clock->fromNow()->willReturn($pointInTime)->shouldBeCalled();

        $event = SomeCommand::fromContent(['name' => 'steph']);

        $message = new Message($event, []);

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $serializedEvent = $serializer->serializeMessage($message);

        $this->assertIsString($serializedEvent['headers'][Header::EVENT_ID->value]);
        $this->assertEquals(SomeCommand::class, $serializedEvent['headers'][Header::EVENT_TYPE->value]);
        $this->assertEquals('some_date_time', $serializedEvent['headers'][Header::EVENT_TIME->value]);
    }

    /**
     * @test
     */
    public function it_raise_exception_if_event_not_instance_of_content_interface_on_serialization(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Message event must be an instance of Domain to be serialized');

        $message = new Message(new stdclass(), []);

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $serializer->serializeMessage($message);
    }

    /**
     * @test
     */
    public function it_raise_exception_with_string_aggregate_id_header_and_missing_aggregate_id_type(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing aggregate id and/or aggregate type headers');

        $pointInTime = $this->prophesize(PointInTime::class);
        $pointInTime->toString()->willReturn('some_date_time')->shouldBeCalled();
        $this->clock->fromNow()->willReturn($pointInTime)->shouldBeCalled();

        $aggregateId = (new GenerateUuidV4())->generate();

        $event = SomeAggregateChanged::occur($aggregateId, ['name' => 'steph']);
        $headers = [Header::AGGREGATE_ID->value => $aggregateId];

        $message = new Message($event, $headers);

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());
        $serializer->serializeMessage($message);
    }

    /**
     * @test
     */
    public function it_unserialize_payload(): void
    {
        $eventId = (new GenerateUuidV4())->generate();
        $eventTime = UniversalPointInTime::now()->toString();
        $eventClass = SomeCommand::class;

        $headers = [
            Header::EVENT_TYPE->value => $eventClass,
            Header::EVENT_ID->value   => $eventId,
            Header::EVENT_TIME->value => $eventTime,
        ];

        $content = ['name' => 'steph'];

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $event = $serializer->unserializeContent([
            'headers' => $headers,
            'content' => $content,
        ])->current();

        $this->assertEquals($eventClass, $event::class);
        $this->assertEquals($content, $event->toContent());

        $this->assertIsString($event->header(Header::EVENT_ID->value));
        $this->assertEquals($eventId, $event->header(Header::EVENT_ID->value));

        $this->assertIsString($event->header(Header::EVENT_TIME->value));
    }

    /**
     * @test
     */
    public function it_unserialize_payload_from_aggregate_changed_source(): void
    {
        $aggregateId = (new GenerateUuidV4())->generate();
        $eventId = (new GenerateUuidV4())->generate();
        $eventTime = UniversalPointInTime::now()->toString();
        $eventClass = SomeAggregateChanged::class;

        $headers = [
            Header::AGGREGATE_ID->value      => $aggregateId,
            Header::AGGREGATE_ID_TYPE->value => Uuid::class,
            Header::INTERNAL_POSITION->value => 1,
            Header::EVENT_TYPE->value        => $eventClass,
            Header::EVENT_ID->value          => $eventId,
            Header::EVENT_TIME->value        => $eventTime,
        ];

        $content = ['name' => 'steph'];

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $event = $serializer->unserializeContent([
            'headers' => $headers,
            'content' => $content,
        ])->current();

        $this->assertEquals($eventClass, $event::class);
        $this->assertEquals($content, $event->toContent());
        $this->assertIsString($event->header(Header::EVENT_ID->value));
        $this->assertEquals($eventId, $event->header(Header::EVENT_ID->value));
        $this->assertIsString($event->header(Header::EVENT_TIME->value));

        $this->assertEquals($aggregateId, $event->header(Header::AGGREGATE_ID->value));
        $this->assertEquals(1, $event->header(Header::INTERNAL_POSITION->value));
    }

    /**
     * @test
     */
    public function it_add_internal_version_header_on_unserializing_aggregate_changed(): void
    {
        $aggregateId = (new GenerateUuidV4())->generate();

        $id = (new GenerateUuidV4())->generate();
        $time = UniversalPointInTime::now();
        $eventClass = SomeAggregateChanged::class;

        $headers = [
            Header::AGGREGATE_ID->value      => $aggregateId,
            Header::AGGREGATE_ID_TYPE->value => Uuid::class,
            Header::EVENT_TYPE->value        => $eventClass,
            Header::EVENT_ID->value          => $id,
            Header::EVENT_TIME->value        => $time->toString(),
        ];

        $content = ['name' => 'steph'];

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $event = $serializer->unserializeContent([
            'headers' => $headers,
            'content' => $content,
            'no'      => 1,
        ])->current();

        $this->assertEquals(1, $event->header(Header::INTERNAL_POSITION->value));
    }

    /**
     * @test
     */
    public function it_raise_exception_with_missing_event_type_header_on_unserializing_event(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing event type header/message name from payload');

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());

        $serializer->unserializeContent(['headers' => []])->current();
    }

    /**
     * @test
     * @dataProvider provideMissingAggregateHeader
     */
    public function it_raise_exception_with_missing_aggregate_id_header_on_serialization(array $headers): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Missing aggregate id and/or aggregate type headers');

        $event = SomeAggregateChanged::occur((new GenerateUuidV4())->generate(), ['name' => 'steph']);

        $headers = $headers + [Header::EVENT_TIME->value => UniversalPointInTime::now()->toString()];
        $message = new Message($event, $headers);

        $serializer = new GenericMessageSerializer($this->clock->reveal(), new GenerateUuidV4());
        $serializer->serializeMessage($message);
    }

    public function provideMissingAggregateHeader(): Generator
    {
        yield [[]];
        yield [[Header::AGGREGATE_ID->value => 'some_aggregate_id']];
        yield [[Header::AGGREGATE_ID_TYPE->value => 'some_aggregate_id_type']];
    }
}
