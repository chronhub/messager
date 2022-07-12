<?php

declare(strict_types=1);

namespace Chronhub\Messager\Tests\Unit\Message;

use Chronhub\Messager\Tests\UnitTestCase;
use Chronhub\Messager\Tests\Double\SomeDomain;

final class DomainTest extends UnitTestCase
{
    /**
     * @test
     */
    public function it_can_be_instantiated(): void
    {
        $domain = SomeDomain::fromContent(['name' => 'steph bug']);

        $this->assertEquals(['name' => 'steph bug'], $domain->toContent());
        $this->assertEmpty($domain->headers());
        $this->assertFalse($domain->has('some_header'));
    }

    /**
     * @test
     */
    public function it_update_header_and_return_new_domain_instance(): void
    {
        $domain = SomeDomain::fromContent([]);

        $this->assertEmpty($domain->headers());
        $this->assertFalse($domain->has('some_header'));

        $newDomain = $domain->withHeader('some_header', true);

        $this->assertNotEquals($newDomain, $domain);

        $this->assertArrayNotHasKey('some_header', $domain->headers());
        $this->assertArrayHasKey('some_header', $newDomain->headers());
        $this->assertTrue($newDomain->header('some_header'));
    }

    /**
     * @test
     */
    public function it_override_headers_and_return_new_domain_instance(): void
    {
        $domain = SomeDomain::fromContent([]);

        $this->assertEmpty($domain->headers());
        $this->assertFalse($domain->has('some_header'));

        $domain = $domain->withHeader('some_header', true);

        $this->assertTrue($domain->header('some_header'));

        $newDomain = $domain->withHeaders(['another_header' => true]);

        $this->assertNotEquals($newDomain, $domain);

        $this->assertArrayNotHasKey('some_header', $newDomain->headers());
        $this->assertArrayHasKey('another_header', $newDomain->headers());
        $this->assertTrue($newDomain->headers()['another_header']);
    }
}
