<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\FilteredParams;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
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

use const JSON_PRESERVE_ZERO_FRACTION;
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
        self::assertRecordedDuration($durationMs);
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
        self::assertRecordedDuration($close['durationMs'] ?? null);
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
        self::assertRecordedDuration($context['durationMs'] ?? null);
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

    public function testFiltersSensitiveParamsByDefaultAndMarksNonReplayable(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/admin/login', ['ok' => true], 200);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
        );

        $invoker->invoke(self::request(
            'app://self/admin/login',
            Method::POST,
            ['loginId' => 'admin', 'password' => 'super-secret'],
        ));

        $entry = self::flushToArray($logger)['open'][0];
        $this->assertSame(['loginId' => 'admin'], $entry['context']['params']);
        $this->assertFalse($entry['context']['replayable']);
    }

    public function testKeepsReplayableTrueWhenNothingIsFiltered(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/products', ['ok' => true], 200);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
        );

        $invoker->invoke(self::request('app://self/products', Method::POST, ['nameKeyword' => 'sample']));

        $entry = self::flushToArray($logger)['open'][0];
        $this->assertSame(['nameKeyword' => 'sample'], $entry['context']['params']);
        $this->assertTrue($entry['context']['replayable']);
    }

    public function testFiltersCsrfTokenByDefaultButKeepsReplayableTrue(): void
    {
        // Transport, not domain input: a replay always mints its own CSRF token regardless of
        // what was recorded, so removing it does not make the recorded params insufficient.
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/shopping/checkout', ['ok' => true], 201);
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
        );

        $invoker->invoke(self::request(
            'app://self/shopping/checkout',
            Method::POST,
            ['preOrderId' => 'aaaa', 'csrfToken' => 'a-token'],
        ));

        $entry = self::flushToArray($logger)['open'][0];
        $this->assertSame(['preOrderId' => 'aaaa'], $entry['context']['params']);
        $this->assertTrue($entry['context']['replayable']);
    }


    public function testCustomParamsFilterOverridesTheDefault(): void
    {
        $logger = new SemanticLogger();
        $ro = new FakeResourceObject('app://self/admin/login', ['ok' => true], 200);
        $passthrough = new class implements ParamsFilterInterface {
            public function __invoke(array $params): FilteredParams
            {
                return new FilteredParams($params);
            }
        };
        $invoker = new SemanticLogInvoker(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $logger,
            new NullBodyStore(),
            paramsFilter: $passthrough,
        );

        $invoker->invoke(self::request(
            'app://self/admin/login',
            Method::POST,
            ['loginId' => 'admin', 'password' => 'super-secret'],
        ));

        $entry = self::flushToArray($logger)['open'][0];
        $this->assertSame(['loginId' => 'admin', 'password' => 'super-secret'], $entry['context']['params']);
        $this->assertTrue($entry['context']['replayable']);
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
     * JSON_PRESERVE_ZERO_FRACTION does not fully stabilize durationMs: koriym/semantic-logger's
     * own ContextFreezer freezes each context through an internal json_encode()/json_decode()
     * round trip (without this flag) before this method's encode ever runs, so a durationMs
     * that happens to round to exactly 0.0 is already an int(0) by the time it reaches here.
     * The flag is kept anyway — it is still correct for any value that does not pass through
     * that internal freeze — but assertRecordedDuration(), not assertIsFloat(), is what a
     * durationMs assertion in this file must use.
     *
     * @return array<string, mixed>
     */
    private static function flushToArray(SemanticLogger $logger): array
    {
        /** @var array<string, mixed> */
        return json_decode(
            json_encode($logger->flush(), JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
            true,
            512,
            JSON_THROW_ON_ERROR,
        );
    }

    /**
     * durationMs is a non-negative int or float depending on whether koriym/semantic-logger's
     * internal freeze happened to round-trip an exact 0.0 through JSON as "0" (see
     * flushToArray()) — a dependency quirk this package does not own, not something this test
     * should be flaky against.
     */
    private static function assertRecordedDuration(mixed $value): void
    {
        self::assertTrue(is_int($value) || is_float($value), 'durationMs must be numeric');
        self::assertGreaterThanOrEqual(0, $value);
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
