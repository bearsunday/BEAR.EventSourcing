<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Tests\Fixture\Link\LinkAppModule;
use BEAR\EventSourcing\Tests\Fixture\Link\Resource\App\Item;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\Exception\LinkRelException;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Koriym\SemanticLogger\AbstractContext;
use Koriym\SemanticLogger\LogJson;
use Koriym\SemanticLogger\SemanticLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;
use RuntimeException;
use TypeError;

use function array_column;
use function array_diff_key;
use function json_decode;
use function json_encode;
use function restore_error_handler;
use function set_error_handler;

use const E_USER_WARNING;
use const JSON_THROW_ON_ERROR;

/** @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design. */
final class SemanticLogLinkerTest extends TestCase
{
    public function testResourceObservationModuleDecoratesTheLinker(): void
    {
        $injector = new Injector(new ResourceObservationModule(module: new LinkAppModule()));

        $this->assertInstanceOf(SemanticLogLinker::class, $injector->getInstance(LinkerInterface::class));
    }

    public function testTheRequestsMadeToFollowTheLinksNestInOneLinkRequest(): void
    {
        $logger = new SemanticLogger();
        $ro = $this->resource($logger)->uri('page://self/form')->linkSelf('goItem')();
        $this->assertSame(['id' => '7'], $ro->body);

        $root = $this->roots($logger)[0];
        $this->assertSame('link_request', $root['type']);
        $this->assertSame(
            ['uri' => 'page://self/form', 'method' => 'GET', 'links' => ['self:goItem']],
            array_diff_key($root['context'], ['timestamp' => true]),
        );
        $requested = array_column(array_column($root['log'], 'context'), 'uri');
        $this->assertSame(['page://self/form', 'app://self/item'], $requested);
        $this->assertSame(['code' => 200], array_diff_key($root['close']['context'], ['durationMs' => true]));
    }

    public function testALinkThatCannotBeFollowedClosesTheLinkRequestWithItsException(): void
    {
        $logger = new SemanticLogger();
        try {
            $this->resource($logger)->uri('page://self/form')->linkSelf('missing')();
            $this->fail('LinkRelException should be thrown.');
        } catch (LinkRelException) {
        }

        $close = $this->roots($logger)[0]['close']['context'];
        $this->assertSame(LinkRelException::class, $close['exception']['class']);
        $this->assertArrayNotHasKey('code', $close);
    }

    public function testALinkRequestIsClosedWhenItsResponseCannotBeRecorded(): void
    {
        $odd = new Item();
        /** @psalm-suppress InvalidPropertyAssignmentValue An ill-behaved resource whose code is a string */
        $odd->code = '200';
        $logger = new SemanticLogger();
        $linker = new SemanticLogLinker(new class ($odd) implements LinkerInterface {
            public function __construct(private readonly ResourceObject $ro)
            {
            }

            public function invoke(AbstractRequest $request): ResourceObject
            {
                return $this->ro;
            }
        }, $logger, new RecordedMethods(RecordedMethods::WITH_READS));
        $request = $this->resource(new SemanticLogger())->uri('page://self/form')->linkSelf('goItem');
        $this->assertInstanceOf(AbstractRequest::class, $request);

        $warnings = [];
        self::withWarnings(static fn (): ResourceObject => $linker->invoke($request), $warnings);

        $this->assertStringContainsString('context build failed', $warnings[0] ?? '');
        // Closed, so it takes no later entry in as its child
        $this->assertSame(TypeError::class, $this->roots($logger)[0]['close']['context']['exception']['class']);
    }

    public function testGetOnlyLinksUnderTheDefaultMethodsWriteNothing(): void
    {
        $logger = new SemanticLogger();
        $injector = new Injector(new ResourceObservationModule(logger: $logger, module: new LinkAppModule()));

        $injector->getInstance(ResourceInterface::class)->uri('page://self/form')->linkSelf('goItem')();

        $this->assertSame([], $this->roots($logger));
    }

    #[DataProvider('failingLoggerMethods')]
    public function testALoggerThatFailsNeverBreaksTheRequest(string $failing): void
    {
        $resource = $this->resource(self::failingLogger($failing));

        $warnings = [];
        $ro = self::withWarnings(
            static fn (): ResourceObject => $resource->uri('page://self/form')->linkSelf('goItem')(),
            $warnings,
        );

        $this->assertSame(['id' => '7'], $ro->body);
        $this->assertNotSame([], $warnings);
    }

    /** @return array<string, array{string}> */
    public static function failingLoggerMethods(): array
    {
        return ['open' => ['open'], 'close' => ['close']];
    }

    private function resource(SemanticLoggerInterface $logger): ResourceInterface
    {
        $module = new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            logger: $logger,
            module: new LinkAppModule(),
        );

        return (new Injector($module))->getInstance(ResourceInterface::class);
    }

    /** @return list<array<string, mixed>> */
    private function roots(SemanticLogger $logger): array
    {
        /** @var array{log: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $tree['log'];
    }

    /**
     * @param callable(): ResourceObject $call
     * @param list<string>               $warnings the user warnings the call raised
     */
    private static function withWarnings(callable $call, array &$warnings): ResourceObject
    {
        set_error_handler(static function (int $errno, string $errstr) use (&$warnings): bool {
            $warnings[] = $errstr;

            return true;
        }, E_USER_WARNING);
        try {
            return $call();
        } finally {
            restore_error_handler();
        }
    }

    private static function failingLogger(string $failing): SemanticLoggerInterface
    {
        return new class ($failing) implements SemanticLoggerInterface {
            public function __construct(private readonly string $failing)
            {
            }

            public function open(AbstractContext $context): string
            {
                if ($this->failing === 'open') {
                    throw new RuntimeException('sink down');
                }

                return 'open-id';
            }

            public function event(AbstractContext $context): void
            {
            }

            public function close(AbstractContext $context, string $openId): void
            {
                if ($this->failing === 'close') {
                    throw new RuntimeException('sink down');
                }
            }

            /** @param list<array{href: string, rel: string, title?: string, type?: string}> $links */
            public function flush(array $links = []): LogJson
            {
                throw new RuntimeException('unused');
            }
        };
    }
}
