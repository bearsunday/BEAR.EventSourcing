<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\Profile;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ErrorContextTest extends TestCase
{
    public function testConstructor(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ErrorContext($exception);

        $this->assertSame($exception, $context->exception);
        $this->assertInstanceOf(AbstractContext::class, $context->context);
        $this->assertNotEmpty($context->id);
    }

    public function testWithProfile(): void
    {
        $exception = new RuntimeException('Test error');
        $phpProfile = new PhpProfile();
        $profile = new Profile(null, null, $phpProfile);

        $context = new ErrorContext($exception, $profile);

        $this->assertSame($profile, $context->profile);
    }

    public function testWithCustomId(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ErrorContext($exception, null, 'custom-id');

        $this->assertSame('custom-id', $context->id);
    }

    public function testIdIsGenerated(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ErrorContext($exception);

        // ID should be 8 hex characters
        $this->assertMatchesRegularExpression('/^[0-9a-f]{8}$/', $context->id);
    }

    public function testSameExceptionGeneratesSameId(): void
    {
        $exception = new RuntimeException('Test error', 0);

        $context1 = new ErrorContext($exception);
        $context2 = new ErrorContext($exception);

        $this->assertSame($context1->id, $context2->id);
    }
}
