<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;

final class DomainCommandTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_assert_command_type(): void
    {
        $command = SomeCommand::fromContent([]);

        $this->assertEquals('command', $command->type());
    }
}
