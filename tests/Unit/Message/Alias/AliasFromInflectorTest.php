<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Alias;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeCommand;
use Chronhub\Messager\Message\Alias\AliasFromInflector;
use Chronhub\Messager\Exceptions\InvalidArgumentException;

final class AliasFromInflectorTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_return_event_class_from_event_string(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertEquals('some-command', $messageAlias->classToAlias($event::class));
    }

    /**
     * @test
     */
    public function it_raise_exception_when_event_class_string_does_not_exists(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Event class invalid_event does not exists');

        $messageAlias = new AliasFromInflector();

        $messageAlias->classToAlias('invalid_event');
    }

    /**
     * @test
     */
    public function it_return_alias_from_event_object(): void
    {
        $event = SomeCommand::fromContent(['name' => 'steph']);

        $messageAlias = new AliasFromInflector();

        $this->assertEquals('some-command', $messageAlias->instanceToAlias($event));
    }
}
