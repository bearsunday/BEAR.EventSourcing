<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ResourceResponseContext;
use JsonSchema\Validator;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function dirname;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

final class ResourceResponseContextTest extends TestCase
{
    public function testSuccessSerializesToASchemaValidContext(): void
    {
        $context = ResourceResponseContext::success(200, 'file:///var/es/bodies/000001.json', durationMs: 1.5);

        self::assertSchemaValid($context->jsonSerialize());
    }

    public function testFailureWithAnEscapedExceptionOmitsCode(): void
    {
        // The invoker runs ahead of the application's error handler, so a RuntimeException
        // with no declared HTTP status must not mint a code — a guessed code is exactly the
        // regression this context exists to make unrepresentable.
        $context = ResourceResponseContext::failure(new RuntimeException('boom'));
        $serialized = $context->jsonSerialize();

        self::assertArrayNotHasKey('code', $serialized);
        self::assertSame(['class' => RuntimeException::class, 'message' => 'boom'], $serialized['exception']);
        self::assertSchemaValid($serialized);
    }

    public function testFailureWithAKnownCodeKeepsIt(): void
    {
        // A body-store failure after a resource invocation already completed: the real code
        // is known, so it is kept alongside the exception rather than dropped.
        $context = ResourceResponseContext::failure(new RuntimeException('boom'), code: 500);
        $serialized = $context->jsonSerialize();

        self::assertSame(500, $serialized['code']);
        self::assertSchemaValid($serialized);
    }

    /** @param array<string, mixed> $context */
    private static function assertSchemaValid(array $context): void
    {
        $validator = new Validator();
        /** @psalm-suppress MixedAssignment json_decode's return is inherently mixed */
        $data = json_decode(json_encode($context, JSON_THROW_ON_ERROR));
        $schemaFile = dirname(__DIR__, 2) . '/docs/schemas/resource-response.json';
        $schema = (object) ['$ref' => 'file://' . $schemaFile];

        $validator->validate($data, $schema);

        self::assertTrue(
            $validator->isValid(),
            json_encode($validator->getErrors(), JSON_THROW_ON_ERROR),
        );
    }
}
