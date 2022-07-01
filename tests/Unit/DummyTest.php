<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit;

use Chronhub\Messager\Tests\TestCase;

final class DummyTest extends TestCase
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
