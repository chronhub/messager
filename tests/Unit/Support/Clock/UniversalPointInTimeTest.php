<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Support\Clock;

use DateTimeZone;
use DateTimeImmutable;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Support\Clock\UniversalPointInTime;
use Chronhub\Messager\Exceptions\InvalidArgumentException;

final class UniversalPointInTimeTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_assert_format_constant(): void
    {
        $time = UniversalPointInTime::now();

        $this->assertEquals('Y-m-d\TH:i:s.u', $time::DATE_TIME_FORMAT);
    }

    /**
     * @test
     */
    public function it_create_point_int_time_from_now(): void
    {
        $pointInTime = UniversalPointInTime::now();

        $this->assertEquals('UTC', $pointInTime->dateTime()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function it_create_point_in_time_from_date_time(): void
    {
        $time = new DateTimeImmutable('now', new DateTimeZone('UTC'));

        $pointInTime = UniversalPointInTime::fromDateTime($time);

        $this->assertEquals($time, $pointInTime->dateTime());
    }

    /**
     * @test
     */
    public function it_create_utc_point_in_time_from_different_date_time_zone(): void
    {
        $time = new DateTimeImmutable('now', new DateTimeZone('EUROPE/PARIS'));

        $pointInTime = UniversalPointInTime::fromDateTime($time);

        $this->assertEquals($time, $pointInTime->dateTime());

        $this->assertNotEquals($time->getTimezone()->getName(), $pointInTime->dateTime()->getTimezone()->getName());
        $this->assertEquals('UTC', $pointInTime->dateTime()->getTimezone()->getName());
    }

    /**
     * @test
     */
    public function it_create_point_in_time_from_string_date_time(): void
    {
        $timeString = UniversalPointInTime::now()->toString();

        $pointInTime = UniversalPointInTime::fromString($timeString);

        $this->assertEquals($timeString, $pointInTime->toString());
    }

    /**
     * @test
     */
    public function it_raise_exception_from_invalid_string_date_time(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid date time');

        UniversalPointInTime::fromString('invalid date');
    }

    /**
     * @test
     */
    public function it_can_be_serialized(): void
    {
        $timeString = UniversalPointInTime::now()->toString();

        $pointInTime = UniversalPointInTime::fromString($timeString);

        $this->assertEquals($timeString, $pointInTime->toString());
        $this->assertEquals($timeString, (string) $pointInTime);
    }

    /**
     * @test
     */
    public function it_compare_point_in_time(): void
    {
        $pointInTime = UniversalPointInTime::now();

        $this->assertNotEquals($pointInTime, $this->anotherPointInTime());
        $this->assertEquals($pointInTime, $pointInTime);

        $this->assertTrue($pointInTime->equals($pointInTime));
        $this->assertFalse($pointInTime->equals($this->anotherPointInTime()));
    }

    /**
     * @test
     */
    public function it_check_point_in_time_after(): void
    {
        $pointInTime = UniversalPointInTime::now();
        $anotherPointInTime = $this->anotherPointInTime();

        $this->assertTrue($anotherPointInTime->after($pointInTime));
        $this->assertFalse($anotherPointInTime->after($anotherPointInTime));
    }

    /**
     * @test
     */
    public function it_return_diff_point_in_time_as_interval(): void
    {
        $pointInTime = UniversalPointInTime::now();
        $inPast = $pointInTime->sub('PT10S');
        $diff = $pointInTime->diff($inPast);

        $this->assertEquals(10, $diff->s);
        $this->assertEquals(1, $diff->invert);
    }

    /**
     * @test
     */
    public function it_add_interval_to_point_in_time(): void
    {
        $pointInTime = UniversalPointInTime::now();

        $inFuture = $pointInTime->add('PT10S');

        $this->assertNotEquals($pointInTime, $inFuture);

        $diff = $pointInTime->diff($inFuture);
        $this->assertEquals(10, $diff->s);
        $this->assertEquals(0, $diff->invert);
    }

    /**
     * @test
     */
    public function it_subtract_interval_to_point_in_time(): void
    {
        $pointInTime = UniversalPointInTime::now();

        $inPast = $pointInTime->sub('PT10S');

        $this->assertNotEquals($pointInTime, $inPast);

        $diff = $pointInTime->diff($inPast);
        $this->assertEquals(10, $diff->s);
        $this->assertEquals(1, $diff->invert);
    }

    private function anotherPointInTime(): UniversalPointInTime
    {
        return UniversalPointInTime::now();
    }
}
