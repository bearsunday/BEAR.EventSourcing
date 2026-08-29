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
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 */
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

        $entry = self::flushToArray($logger)['open'][0];
        // The uri stays canonical (path only); the query lives in params, not the uri.
        $this->assertSame('app://self/user/1', $entry['context']['uri']);
        $this->assertSame('POST', $entry['context']['method']);
        $this->assertSame(['id' => 1], $entry['context']['params']);
        $close = self::closeContext($entry);
        $durationMs = $close['durationMs'] ?? null;
        $this->assertIsFloat($durationMs);
        $this->assertGreaterThanOrEqual(0.0, $durationMs);
        unset($close['durationMs']);
        $this->assertSame(['code' => 201], $close);
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

        // Nothing was recorded: the session flushes to an empty log.
        $this->assertSame([], self::flushToArray($logger)['open']);
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

        $entry = self::flushToArray($logger)['open'][0];
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

        $entry = self::flushToArray($logger)['open'][0];
        $this->assertSame(1, $store->calls);
        $close = self::closeContext($entry);
        $this->assertIsFloat($close['durationMs'] ?? null);
        unset($close['durationMs']);
        $this->assertSame(
            ['code' => 200, 'body_ref' => 'file://var/es/bodies/000001.json'],
            $close,
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
        $entry = self::flushToArray($logger)['open'][0];
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

        $entry = self::flushToArray($logger)['open'][0];
        $context = self::closeContext($entry);
        $exceptionContext = $context['exception'] ?? null;
        $this->assertIsArray($exceptionContext);
        /** @var array{class: string, message: string} $exceptionContext */
        $this->assertSame(500, $context['code']);
        $this->assertIsFloat($context['durationMs'] ?? null);
        $this->assertSame(RuntimeException::class, $exceptionContext['class']);
        $this->assertSame('boom', $exceptionContext['message']);
    }

    public function testLeakedOpenContextDoesNotBreakRequest(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/order', ['ok' => true], 201);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($logger, $ro): FakeResourceObject {
                // A lower layer leaks an unclosed context: its own bug, but the request succeeded.
                $logger->open(
                    new ResourceRequestContext('app://self/leak', 'POST', [], '2026-06-10T12:34:56.123456+00:00'),
                );

                return $ro;
            }),
            $logger,
            new NullBodyStore(),
        );

        $result = $invoker->invoke(self::request('app://self/order', Method::POST));

        // Observation must never break the request, whether the logger rejects
        // the out-of-order close silently or by throwing.
        $this->assertSame($ro, $result);
    }

    public function testLeakedOpenContextPreservesDomainException(): void
    {
        $logger = new SemanticLogger();
        $domain = new DomainException('the real business error');
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static function () use ($logger, $domain): never {
                $logger->open(
                    new ResourceRequestContext('app://self/leak', 'POST', [], '2026-06-10T12:34:56.123456+00:00'),
                );

                throw $domain;
            }),
            $logger,
            new NullBodyStore(),
        );

        $caught = null;
        try {
            $invoker->invoke(self::request('app://self/leak', Method::POST));
            $this->fail('Exception was not thrown.');
        } catch (DomainException $e) {
            $caught = $e;
        }

        // The domain exception survives; it is not masked by the close-time rejection.
        $this->assertSame($domain, $caught);
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

        $outerEntry = self::flushToArray($logger)['open'][0];
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
     * The canonical JSON view of the flushed log: frozen context values arrive
     * as objects, and the assoc-array decode is what the extractor reads too.
     *
     * @return array<string, mixed>
     */
    private static function flushToArray(SemanticLogger $logger): array
    {
        /** @var array<string, mixed> */
        return json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
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
