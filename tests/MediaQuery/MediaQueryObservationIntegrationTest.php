<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\MediaQuery;

use BEAR\EventSourcing\Event;
use BEAR\EventSourcing\EventStoreInterface;
use BEAR\EventSourcing\Module\MediaQueryObservationModule;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\ResourceResponseContext;
use BEAR\EventSourcing\Tests\Fixture\MediaQueryEventStoreAppModule;
use DateTimeImmutable;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PDO;
use PHPUnit\Framework\Attributes\RequiresPhpExtension;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;

use function file_get_contents;
use function json_decode;
use function json_encode;
use function sys_get_temp_dir;
use function tempnam;
use function unlink;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 */
#[RequiresPhpExtension('pdo_sqlite')]
final class MediaQueryObservationIntegrationTest extends TestCase
{
    private string|null $databaseFile = null;

    protected function tearDown(): void
    {
        if ($this->databaseFile !== null) {
            @unlink($this->databaseFile);
        }
    }

    public function testRealSqlQueryExecutionEmitsMediaQueryEvent(): void
    {
        $databaseFile = tempnam(sys_get_temp_dir(), 'bear_es_mq_');
        $this->assertIsString($databaseFile);
        $this->databaseFile = $databaseFile;
        $schema = file_get_contents(__DIR__ . '/../../sql/event_store/schema.sql');
        $this->assertIsString($schema);
        (new PDO('sqlite:' . $databaseFile))->exec($schema);

        // Observation binds before the MediaQuery modules; install() keeps
        // the binding the installer already holds.
        $injector = new Injector(new class ($databaseFile) extends AbstractModule {
            public function __construct(private readonly string $databaseFile)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new MediaQueryObservationModule());
                $this->bind(SemanticLoggerInterface::class)->to(SemanticLogger::class)->in(Scope::SINGLETON);
                $this->install(new MediaQueryEventStoreAppModule($this->databaseFile));
            }
        });
        $logger = $injector->getInstance(SemanticLoggerInterface::class);
        $store = $injector->getInstance(EventStoreInterface::class);

        $openId = $logger->open(new ResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            params: ['order_id' => 'O-1000'],
            timestamp: '2026-06-10T12:36:00.000000+00:00',
        ));
        // A real Ray.MediaQuery execution: SqlQuery::perform() runs the
        // event_store_append query through the observed logger seam.
        $store->append(new Event(
            uri: 'app://self/orders',
            method: 'POST',
            timestamp: new DateTimeImmutable('2026-06-10T12:36:00.000000+00:00'),
            params: ['order_id' => 'O-1000'],
        ));
        $logger->close(new ResourceResponseContext(201), $openId);

        /** @var array{open: list<array<array-key, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $event = $tree['open'][0]['events'][0];
        $this->assertSame('media_query', $event['type']);
        $this->assertSame('event_store_append', $event['context']['name']);
        // An integral millisecond value degrades to int in the JSON roundtrip.
        $this->assertIsNumeric($event['context']['durationMs']);
        $this->assertGreaterThanOrEqual(0.0, $event['context']['durationMs']);
    }
}
