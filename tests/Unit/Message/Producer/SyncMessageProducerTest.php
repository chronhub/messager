<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message\Producer;

use stdClass;
use Chronhub\Messager\Message\Message;
use Chronhub\Messager\Tests\TestCaseWithProphecy;
use Chronhub\Messager\Message\Producer\SyncMessageProducer;

final class SyncMessageProducerTest extends TestCaseWithProphecy
{
    /**
     * @test
     */
    public function it_return_true_on_is_async_method(): void
    {
        $message = new Message(new stdClass());

        $producer = new SyncMessageProducer();

        $this->assertTrue($producer->isSync($message));
    }

    /**
     * @test
     */
    public function it_return_message(): void
    {
        $message = new Message(new stdClass());

        $producer = new SyncMessageProducer();

        $this->assertEquals($message, $producer->produce($message));
    }
}
