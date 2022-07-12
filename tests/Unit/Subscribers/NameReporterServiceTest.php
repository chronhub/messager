<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Subscribers;

use stdClass;
use Generator;
use Chronhub\Messager\Reporter;
use Chronhub\Messager\ReportEvent;
use Chronhub\Messager\ReportCommand;
use Chronhub\Messager\Message\Header;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tracker\TrackMessage;
use Chronhub\Messager\Subscribers\NameReporterService;

final class NameReporterServiceTest extends UnitTestCase
{
    /**
     * @test
     * @dataProvider provideReporterServiceId
     */
    public function it_mark_reporter_service_name_in_message_header(string $serviceId): void
    {
        $tracker = new TrackMessage();

        $subscriber = new NameReporterService($serviceId);
        $subscriber->attachToTracker($tracker);

        $message = new Message(new stdClass());

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker->fire($context);

        $this->assertEquals($serviceId, $context->message()->header(Header::REPORTER_NAME->value));
    }

    /**
     * @test
     */
    public function it_does_not_mark_bus_name_header_if_already_exists(): void
    {
        $tracker = new TrackMessage();

        $subscriber = new NameReporterService('reporter.service_id');
        $subscriber->attachToTracker($tracker);

        $message = new Message(new stdClass(), [Header::REPORTER_NAME->value => 'my_service_id']);

        $context = $tracker->newContext(Reporter::DISPATCH_EVENT);
        $context->withMessage($message);

        $tracker->fire($context);

        $this->assertEquals('my_service_id', $context->message()->header(Header::REPORTER_NAME->value));
    }

    public function provideReporterServiceId(): Generator
    {
        yield ['reporter.service_id'];

        yield [ReportCommand::class];

        yield [ReportEvent::class];
    }
}
