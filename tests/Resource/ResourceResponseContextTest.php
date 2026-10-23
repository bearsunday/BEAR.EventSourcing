<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ResourceResponseContext;
use BEAR\EventSourcing\Resource\ResourceResponseContextException;
use Exception;
use PHPUnit\Framework\TestCase;

final class ResourceResponseContextTest extends TestCase
{
    public function testRejectsNeitherCodeNorException(): void
    {
        $this->expectException(ResourceResponseContextException::class);

        new ResourceResponseContext(null);
    }

    public function testAcceptsCodeWithoutException(): void
    {
        $context = new ResourceResponseContext(200);

        self::assertSame(200, $context->code);
    }

    public function testAcceptsExceptionWithoutCode(): void
    {
        $context = new ResourceResponseContext(null, exception: ['class' => Exception::class, 'message' => 'boom']);

        self::assertNull($context->code);
    }
}
