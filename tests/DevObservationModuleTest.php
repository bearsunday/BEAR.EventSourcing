<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Module\DevObservationModule;
use BEAR\EventSourcing\Resource\FilteredParams;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Tests\Fixture\AppLinker;
use BEAR\EventSourcing\Tests\Fixture\CountingInterceptor;
use BEAR\EventSourcing\Tests\Fixture\DevContextChainModule;
use BEAR\QueryRepository\Log\LogSinkInterface;
use BEAR\RepositoryModule\Annotation\CacheLog;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use Ray\Di\Scope;
use ReflectionProperty;

use function file_get_contents;
use function glob;
use function is_array;
use function is_dir;
use function json_decode;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress NoInterfaceProperties,MixedFunctionCall,MixedMethodCall,MixedAssignment
 *     The resource client is driven through its magic verb properties.
 */
final class DevObservationModuleTest extends TestCase
{
    use RemovesDirectoryTree;

    private string $logDir = '';

    protected function setUp(): void
    {
        $this->logDir = sys_get_temp_dir() . '/' . uniqid('bear-es-dev-', true);
    }

    protected function tearDown(): void
    {
        if (! is_dir($this->logDir)) {
            return;
        }

        self::removeTree($this->logDir);
    }

    public function testOneRequestReadsAsOneTreeAndItsBodyIsStored(): void
    {
        $root = $this->requestOnce($this->devContext());

        $this->assertSame('resource_request', $root['type']);
        $this->assertTrue(self::subtreeHasType($root, 'get'), 'the cache scope nests inside the resource request');
        $bodies = glob($this->logDir . '/events/bodies/*/*.json');
        $this->assertNotSame([], $bodies, 'the response body is stored under the given directory');
        /** @psalm-suppress MixedArrayAccess The canonical JSON view is untyped by design. */
        $caller = $root['context']['caller']['file'] ?? null;
        $this->assertSame(__FILE__, $caller, 'the request records where it was made');
    }

    public function testAnAppBoundParamsFilterReachesTheInvoker(): void
    {
        $filter = new class extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(ParamsFilterInterface::class)->annotatedWith(Filtered::class)->toInstance(
                    new class implements ParamsFilterInterface {
                        /** {@inheritDoc} */
                        public function __invoke(array $params): FilteredParams
                        {
                            return new FilteredParams(['name' => '[APP]']);
                        }
                    },
                );
            }
        };

        $root = $this->requestOnce($this->devContext($filter));

        $context = $root['context'] ?? null;
        $this->assertIsArray($context);
        $this->assertSame(['name' => '[APP]'], $context['params'] ?? null);
    }

    public function testARequestWithLinksReadsAsOneLinkRequest(): void
    {
        $injector = $this->devContext();
        $this->assertInstanceOf(SemanticLogLinker::class, $injector->getInstance(LinkerInterface::class));

        $root = $this->requestOnce(
            $injector,
            static fn (ResourceInterface $resource): mixed => $resource->uri('app://self/hello?name=koriym')
                ->linkSelf('greet')(),
        );

        $this->assertSame('link_request', $root['type']);
        $log = $root['log'] ?? null;
        $this->assertIsArray($log);
        $this->assertCount(2, $log);
    }

    public function testTheRecordingWrapsTheLinkerTheAppChainBinds(): void
    {
        $chain = new class (new DevContextChainModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(LinkerInterface::class)->to(AppLinker::class);
            }
        };

        $linker = $this->devContext(chain: $chain)->getInstance(LinkerInterface::class);

        $this->assertInstanceOf(SemanticLogLinker::class, $linker);
        $this->assertInstanceOf(AppLinker::class, (new ReflectionProperty($linker, 'linker'))->getValue($linker));
    }

    public function testAnInterceptorTheAppChainBindsRunsOncePerCall(): void
    {
        $chain = new class (new DevContextChainModule()) extends AbstractModule {
            protected function configure(): void
            {
                $this->bind(CountingInterceptor::class)->in(Scope::SINGLETON);
                $this->bindInterceptor(
                    $this->matcher->any(),
                    $this->matcher->startsWith('onGet'),
                    [CountingInterceptor::class],
                );
            }
        };
        $injector = $this->devContext(chain: $chain);

        $this->requestOnce($injector);

        $this->assertSame(1, $injector->getInstance(CountingInterceptor::class)->calls);
    }

    /** A `dev-` context module the way BEAR.Package builds it: constructed with the application chain */
    private function devContext(
        AbstractModule|null $app = null,
        AbstractModule $chain = new DevContextChainModule(),
    ): Injector {
        $context = new class ($this->logDir, $app, $chain) extends AbstractModule {
            public function __construct(
                private readonly string $logDir,
                private readonly AbstractModule|null $app,
                AbstractModule $chain,
            ) {
                parent::__construct($chain);
            }

            protected function configure(): void
            {
                $this->rename(InvokerInterface::class, DevObservationModule::INVOKER);
                $this->rename(LinkerInterface::class, DevObservationModule::LINKER);
                $this->install(new DevObservationModule($this->logDir . '/events', 10));
                if ($this->app === null) {
                    return;
                }

                $this->install($this->app);
            }
        };

        return new Injector($context);
    }

    /**
     * @param (callable(ResourceInterface): mixed)|null $request the request to make, a GET of the greeting by default
     *
     * @return array<string, mixed> the first root of the tree the sink wrote
     */
    private function requestOnce(Injector $injector, callable|null $request = null): array
    {
        $resource = $injector->getInstance(ResourceInterface::class);
        $request === null ? $resource->get->uri('app://self/greeting')(['name' => 'koriym']) : $request($resource);
        $sink = $injector->getInstance(LogSinkInterface::class);
        $this->assertInstanceOf(LogSinkInterface::class, $sink);
        $logger = $injector->getInstance(SemanticLoggerInterface::class, CacheLog::class);
        $this->assertInstanceOf(SemanticLoggerInterface::class, $logger);
        $sink->flush($logger);

        $latest = (string) file_get_contents($this->logDir . '/events/latest.json');
        /** @var array{log: list<array<string, mixed>>} $tree */
        $tree = json_decode($latest, true, 512, JSON_THROW_ON_ERROR);
        $this->assertNotSame([], $tree['log']);

        return $tree['log'][0];
    }

    /** @param array<array-key, mixed> $entry */
    private static function subtreeHasType(array $entry, string $type): bool
    {
        foreach ($entry['log'] ?? [] as $child) {
            if (! is_array($child)) {
                continue;
            }

            if (($child['type'] ?? null) === $type || self::subtreeHasType($child, $type)) {
                return true;
            }
        }

        return false;
    }
}
