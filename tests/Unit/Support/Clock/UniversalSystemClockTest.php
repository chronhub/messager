<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Support\Clock;

use DateTimeZone;
use DateTimeImmutable;
use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Support\Clock\UniversalPointInTime;
use Chronhub\Messager\Support\Clock\UniversalSystemClock;

final class UniversalSystemClockTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_return_point_in_time_from_now(): void
    {
        $clock = new UniversalSystemClock();

        $this->assertInstanceOf(UniversalPointInTime::class, $clock->fromNow());
    }

    /**
     * @test
     */
    public function it_return_point_in_time_from_utc_date_time(): void
    {
        $clock = new UniversalSystemClock();

        $dateTime = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $this->assertInstanceOf(UniversalPointInTime::class, $clock->fromNow());

        $this->assertEquals($dateTime, $clock->fromDateTime($dateTime)->dateTime());
    }

    /**
     * @test
     */
    public function it_return_utc_point_in_time_from_date_time_with_another_time_zone(): void
    {
        $clock = new UniversalSystemClock();

        $europeDateTime = new DateTimeImmutable('now', new DateTimeZone('EUROPE/PARIS'));

        $pointInTime = $clock->fromDateTime($europeDateTime);

        $this->assertInstanceOf(UniversalPointInTime::class, $pointInTime);

        $utcDateTime = $pointInTime->dateTime();

        $this->assertEquals($europeDateTime, $utcDateTime);

        $this->assertEquals('UTC', $utcDateTime->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function it_return_utc_point_in_time_from_string_date_time_with_another_time_zone(): void
    {
        $clock = new UniversalSystemClock();

        $europeDateTime = new DateTimeImmutable('now', new DateTimeZone('EUROPE/PARIS'));
        $europeDateTimeString = $europeDateTime->format(UniversalPointInTime::DATE_TIME_FORMAT);

        $pointInTime = $clock->fromString($europeDateTimeString);

        $this->assertInstanceOf(UniversalPointInTime::class, $pointInTime);

        $utcDateTime = $pointInTime->dateTime();

        $this->assertNotEquals($europeDateTime, $utcDateTime);

        $this->assertEquals('UTC', $utcDateTime->getTimezone()->getName());
    }
}
