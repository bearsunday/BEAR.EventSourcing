<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use DomainException;
use Koriym\SemanticLogger\Exception\NoLogSessionException;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;

final class SemanticLogInvokerTest extends TestCase
{
    public function testCreatesOpenCloseLog(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/user/1', ['id' => 1], 201);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
        );

        $invoker->invoke(self::request('app://self/user/1', Method::POST, ['id' => 1]));

        $entry = $logger->flush()->toArray()['open'][0];
        $this->assertSame('app://self/user/1?id=1', $entry['context']['uri']);
        $this->assertSame('POST', $entry['context']['method']);
        $this->assertSame(['id' => 1], $entry['context']['params']);
        $this->assertSame(['code' => 201], self::closeContext($entry));
    }

    public function testSkipsGetByDefault(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/user/1', ['id' => 1]);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
        );

        $invoker->invoke(self::request('app://self/user/1', Method::GET));

        $this->expectException(NoLogSessionException::class);
        $logger->flush();
    }

    public function testRecordsGetWhenConfigured(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/user/1', ['id' => 1]);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
            new RecordedMethods(RecordedMethods::WITH_READS),
        );

        $invoker->invoke(self::request('app://self/user/1', Method::GET));

        $entry = $logger->flush()->toArray()['open'][0];
        $this->assertSame('GET', $entry['context']['method']);
    }

    public function testAddsBodyRefWhenBodyStoreReturnsReference(): void
    {
        $logger = new SemanticLogger();
        $store = new RecordingBodyStore('file://var/es/bodies/000001.json');
        $ro = new FakeResourceObject('app://self/user/1', ['id' => 1]);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            $store,
        );

        $invoker->invoke(self::request('app://self/user/1', Method::POST));

        $entry = $logger->flush()->toArray()['open'][0];
        $this->assertSame(1, $store->calls);
        $this->assertSame(
            ['code' => 200, 'body_ref' => 'file://var/es/bodies/000001.json'],
            self::closeContext($entry),
        );
    }

    public function testBodyStoreFailureDoesNotBreakRequest(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/user/1', ['id' => 1], 201);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new ThrowingBodyStore(),
        );

        $result = $invoker->invoke(self::request('app://self/user/1', Method::POST));

        $this->assertSame($ro, $result);
        $entry = $logger->flush()->toArray()['open'][0];
        $context = self::closeContext($entry);
        $exceptionContext = $context['exception'] ?? null;
        $this->assertIsArray($exceptionContext);
        /** @var array{class: string, message: string} $exceptionContext */
        $this->assertSame(201, $context['code']);
        $this->assertSame(BodyStoreException::class, $exceptionContext['class']);
        $this->assertSame('The body store failed.', $exceptionContext['message']);
    }

    public function testClosesAndRethrowsOnException(): void
    {
        $logger = new SemanticLogger();
        $exception = new RuntimeException('boom');
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($exception): never {
                throw $exception;
            }),
            $logger,
            new NullBodyStore(),
        );

        try {
            $invoker->invoke(self::request('app://self/user/1', Method::POST));
            $this->fail('Exception was not thrown.');
        } catch (RuntimeException $e) {
            $this->assertSame($exception, $e);
        }

        $entry = $logger->flush()->toArray()['open'][0];
        $context = self::closeContext($entry);
        $exceptionContext = $context['exception'] ?? null;
        $this->assertIsArray($exceptionContext);
        /** @var array{class: string, message: string} $exceptionContext */
        $this->assertSame(500, $context['code']);
        $this->assertSame(RuntimeException::class, $exceptionContext['class']);
        $this->assertSame('boom', $exceptionContext['message']);
    }

    public function testCloseFailureOnSuccessPathDoesNotBreakRequest(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/order', ['ok' => true], 201);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($logger, $ro): FakeResourceObject {
                // A lower layer leaks an unclosed context: its own bug, but the request succeeded.
                $logger->open(new ResourceRequestContext('app://self/leak', 'POST'));

                return $ro;
            }),
            $logger,
            new NullBodyStore(),
        );

        $warning = self::captureWarning(
            static fn (): object => $invoker->invoke(self::request('app://self/order', Method::POST)),
        );

        $this->assertSame($ro, $warning['result']);
        $this->assertStringContainsString('Semantic log close failed', $warning['message']);
    }

    public function testCloseFailureOnErrorPathPreservesDomainException(): void
    {
        $logger = new SemanticLogger();
        $domain = new DomainException('the real business error');
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($logger, $domain): never {
                $logger->open(new ResourceRequestContext('app://self/leak', 'POST'));

                throw $domain;
            }),
            $logger,
            new NullBodyStore(),
        );

        $caught = null;
        $message = null;
        set_error_handler(static function (int $_severity, string $text) use (&$message): bool {
            $message = $text;

            return true;
        }, E_USER_WARNING);
        try {
            $invoker->invoke(self::request('app://self/leak', Method::POST));
            $this->fail('Exception was not thrown.');
        } catch (DomainException $e) {
            $caught = $e;
        } finally {
            restore_error_handler();
        }

        // The domain exception survives; it is not masked by the close-time LIFO error.
        $this->assertSame($domain, $caught);
        $this->assertNotNull($message);
    }

    /**
     * @param callable(): object $invoke
     * @return array{result: object|null, message: string}
     */
    private static function captureWarning(callable $invoke): array
    {
        $message = '';
        set_error_handler(static function (int $_severity, string $text) use (&$message): bool {
            $message = $text;

            return true;
        }, E_USER_WARNING);
        try {
            $result = $invoke();
        } finally {
            restore_error_handler();
        }

        return ['result' => $result, 'message' => $message];
    }

    public function testNestedInvocationsKeepSemanticLogTree(): void
    {
        $logger = new SemanticLogger();
        $innerRo = new FakeResourceObject('app://self/inner', ['id' => 2]);
        $inner = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $innerRo),
            $logger,
            new NullBodyStore(),
        );
        $outerRo = new FakeResourceObject('app://self/outer', ['id' => 1]);
        $outer = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($inner, $outerRo): FakeResourceObject {
                $inner->invoke(self::request('app://self/inner', Method::POST));

                return $outerRo;
            }),
            $logger,
            new NullBodyStore(),
        );

        $outer->invoke(self::request('app://self/outer', Method::POST));

        $outerEntry = $logger->flush()->toArray()['open'][0];
        $this->assertSame('app://self/outer', $outerEntry['context']['uri']);
        $this->assertSame('app://self/inner', self::firstChildContext($outerEntry)['uri']);
    }

    /** @param array<string, mixed> $query */
    private static function request(string $uri, Method $method, array $query = []): Request
    {
        $ro = new FakeResourceObject($uri);

        return new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            $method,
            $query,
        );
    }

    /**
     * @param array<array-key, mixed> $entry
     * @return array<string, mixed>
     */
    private static function closeContext(array $entry): array
    {
        $close = $entry['close'] ?? null;
        self::assertIsArray($close);
        $context = $close['context'] ?? null;
        self::assertIsArray($context);

        /** @var array<string, mixed> $context */
        return $context;
    }

    /**
     * @param array<array-key, mixed> $entry
     * @return array<string, mixed>
     */
    private static function firstChildContext(array $entry): array
    {
        $children = $entry['open'] ?? null;
        self::assertIsArray($children);
        $child = $children[0] ?? null;
        self::assertIsArray($child);
        $context = $child['context'] ?? null;
        self::assertIsArray($context);

        /** @var array<string, mixed> $context */
        return $context;
    }
}
