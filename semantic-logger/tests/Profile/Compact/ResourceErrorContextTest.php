<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Compact;

use Koriym\SemanticLogger\AbstractContext;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ResourceErrorContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception);

        $this->assertSame($exception, $context->exception);
        $this->assertNotEmpty($context->id);
    }

    public function testExtendsAbstractContext(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception);

        $this->assertInstanceOf(AbstractContext::class, $context);
    }

    public function testTypeConstant(): void
    {
        $this->assertSame('resource.error', ResourceErrorContext::TYPE);
    }

    public function testWithCustomId(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception, 'custom-id');

        $this->assertSame('custom-id', $context->id);
    }

    public function testIdIsGenerated(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception);

        // ID should be 8 hex characters
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $context->id);
    }
}
