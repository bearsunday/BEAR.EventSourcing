<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\MediaQuery;

use BEAR\EventSourcing\MediaQuery\SemanticLogMediaQueryLogger;
use BEAR\EventSourcing\Module\MediaQueryObservationModule;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\ResourceResponseContext;
use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;
use Ray\MediaQuery\MediaQueryLoggerInterface;
use RuntimeException;

use function base64_encode;
use function json_decode;
use function json_encode;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;
use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 */
final class SemanticLogMediaQueryLoggerTest extends TestCase
{
    public function testEmitsMediaQueryEventUnderOpenScope(): void
    {
        $logger = new SemanticLogger();
        $adapter = new SemanticLogMediaQueryLogger($logger);
        $openId = $logger->open(new ResourceRequestContext(
            uri: 'app://self/inventory',
            method: 'PUT',
            params: ['sku' => 'SKU-1'],
            timestamp: '2026-06-10T12:36:01.000000+00:00',
        ));

        $adapter->start();
        $adapter->log('inventory_reserve', ['sku' => 'SKU-1']);
        $logger->close(new ResourceResponseContext(200), $openId);

        $entry = self::flushToArray($logger->flush())['open'][0];
        $event = $entry['events'][0];
        $this->assertSame('media_query', $event['type']);
        $this->assertSame('inventory_reserve', $event['context']['name']);
        $this->assertSame(['sku' => 'SKU-1'], $event['context']['params']);
        $this->assertIsFloat($event['context']['durationMs']);
        $this->assertGreaterThanOrEqual(0.0, $event['context']['durationMs']);
        $this->assertSame('query: inventory_reserve', (string) $adapter);
    }

    public function testBinaryParamIsBase64Encoded(): void
    {
        $logger = new SemanticLogger();
        $adapter = new SemanticLogMediaQueryLogger($logger);
        $openId = $logger->open(
            new ResourceRequestContext('app://self/blob', 'PUT', [], '2026-06-10T12:00:00.000000+00:00'),
        );

        $adapter->start();
        $adapter->log('blob_save', ['payload' => "\xB1\x31"]);
        $logger->close(new ResourceResponseContext(200), $openId);

        $entry = self::flushToArray($logger->flush())['open'][0];
        $this->assertSame(base64_encode("\xB1\x31"), $entry['events'][0]['context']['params']['payload']);
    }

    public function testLogWithoutStartRecordsZeroDuration(): void
    {
        $logger = new SemanticLogger();
        $adapter = new SemanticLogMediaQueryLogger($logger);
        $openId = $logger->open(
            new ResourceRequestContext('app://self/x', 'POST', [], '2026-06-10T12:00:00.000000+00:00'),
        );

        $adapter->log('unstarted', []);
        $logger->close(new ResourceResponseContext(200), $openId);

        $entry = self::flushToArray($logger->flush())['open'][0];
        // 0.0 loses its float-ness in the JSON roundtrip; the value is what matters.
        $this->assertEquals(0.0, $entry['events'][0]['context']['durationMs']);
    }

    public function testObservationFailureNeverEscapes(): void
    {
        $adapter = new SemanticLogMediaQueryLogger(new class implements SemanticLoggerInterface {
            public function open(AbstractContext $context): string
            {
                return 'x_1';
            }

            public function event(AbstractContext $context): void
            {
                throw new RuntimeException('sink down');
            }

            public function close(AbstractContext $context, string $openId): void
            {
            }

            /** @param list<array{href: string, rel: string, title?: string, type?: string}> $links */
            public function flush(array $links = []): LogJson
            {
                throw new RuntimeException('unused');
            }
        });

        $message = '';
        set_error_handler(static function (int $_severity, string $text) use (&$message): bool {
            $message = $text;

            return true;
        }, E_USER_WARNING);
        try {
            $adapter->start();
            $adapter->log('orders_add', ['id' => 1]); // must not throw
        } finally {
            restore_error_handler();
        }

        $this->assertStringContainsString('Media query observation failed', $message);
    }

    public function testModuleBindsLoggerSeam(): void
    {
        $injector = new Injector(new class extends AbstractModule {
            protected function configure(): void
            {
                $this->install(new MediaQueryObservationModule());
                $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
            }
        });

        $this->assertInstanceOf(
            SemanticLogMediaQueryLogger::class,
            $injector->getInstance(MediaQueryLoggerInterface::class),
        );
    }

    /** @return array{open: list<array<array-key, mixed>>} */
    private static function flushToArray(LogJson $log): array
    {
        /** @var array{open: list<array<array-key, mixed>>} */
        return json_decode(json_encode($log, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }
}
