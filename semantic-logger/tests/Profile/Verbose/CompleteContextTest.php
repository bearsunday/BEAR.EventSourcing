<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\OperationProfile;
use PHPUnit\Framework\TestCase;

final class CompleteContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $context = new CompleteContext(
            'app://self/user',
            200,
            ['Content-Type' => 'application/json'],
            ['id' => 1, 'name' => 'test'],
            '{"id":1,"name":"test"}',
        );

        $this->assertSame('app://self/user', $context->uri);
        $this->assertSame(200, $context->code);
        $this->assertSame(['Content-Type' => 'application/json'], $context->headers);
        $this->assertSame(['id' => 1, 'name' => 'test'], $context->body);
        $this->assertSame('{"id":1,"name":"test"}', $context->view);
        $this->assertInstanceOf(AbstractContext::class, $context->context);
    }

    public function testWithProfile(): void
    {
        $profile = new OperationProfile(0.0);

        $context = new CompleteContext(
            'app://self/user',
            200,
            [],
            null,
            null,
            $profile,
        );

        $this->assertSame($profile, $context->profile);
    }

    public function testWithNullView(): void
    {
        $context = new CompleteContext(
            'app://self/user',
            200,
            [],
            null,
        );

        $this->assertNull($context->view);
        $this->assertNull($context->profile);
    }
}
