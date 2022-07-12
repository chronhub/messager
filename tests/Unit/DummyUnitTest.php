<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit;

use Chronhub\Messager\Tests\UnitTestCase;

final class DummyUnitTest extends UnitTestCase
{
    /**
     * @test
     *
     * @return void
     */
    public function it_assert_true(): void
    {
        $this->assertTrue(true);
    }

    /**
     * @test
     *
     * @return void
     */
    public function it_assert_false(): void
    {
        $this->assertFalse(false);
    }
}
