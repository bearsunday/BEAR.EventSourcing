<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Profile\Verbose;

use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\Profiler\PhpProfile;
use Koriym\SemanticLogger\Profiler\Profile;
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

    public function testWithProfile(): void
    {
        $exception = new RuntimeException('Test error');
        $phpProfile = new PhpProfile();
        $profile = new Profile(null, null, $phpProfile);

        $context = new ResourceErrorContext($exception, $profile);

        $this->assertSame($profile, $context->profile);
    }

    public function testWithCustomId(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception, null, 'custom-id');

        $this->assertSame('custom-id', $context->id);
    }

    public function testJsonSerialize(): void
    {
        $exception = new RuntimeException('Test error', 500);
        $context = new ResourceErrorContext($exception);

        $data = $context->jsonSerialize();

        $this->assertSame($context->id, $data['id']);
        $this->assertIsArray($data['exception']);
        $exceptionData = $data['exception'];
        $this->assertSame(RuntimeException::class, $exceptionData['class']);
        $this->assertSame('Test error', $exceptionData['message']);
        $this->assertSame(500, $exceptionData['code']);
    }

    public function testJsonSerializeWithProfile(): void
    {
        $exception = new RuntimeException('Test error');
        $phpProfile = new PhpProfile();
        $profile = new Profile(null, null, $phpProfile);

        $context = new ResourceErrorContext($exception, $profile);

        $data = $context->jsonSerialize();

        $this->assertArrayHasKey('profile', $data);
    }

    public function testJsonSerializeWithoutProfile(): void
    {
        $exception = new RuntimeException('Test error');
        $context = new ResourceErrorContext($exception);

        $data = $context->jsonSerialize();

        $this->assertArrayNotHasKey('profile', $data);
    }
}
