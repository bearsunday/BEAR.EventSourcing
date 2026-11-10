<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\ResourceRequestContext;
use BEAR\EventSourcing\Resource\ResourceResponseContext;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\Tests\Fixture\Caller\CallerAppModule;
use BEAR\EventSourcing\Tests\Fixture\Caller\Resource\Page\Index;
use BEAR\EventSourcing\Tests\Fixture\Caller\SiteSetting;
use BEAR\EventSourcing\Tests\Fixture\UnifiedLogModule;
use BEAR\EventSourcing\Tests\RemovesDirectoryTree;
use BEAR\Resource\ResourceInterface;
use Closure;
use Composer\InstalledVersions;
use FakeVendor\Unified\Resource\App\Profile;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;
use ReflectionClass;
use ReflectionMethod;

use function dirname;
use function file;
use function file_put_contents;
use function iterator_to_array;
use function json_decode;
use function json_encode;
use function mkdir;
use function str_contains;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 * @psalm-suppress NoInterfaceProperties,MixedFunctionCall,MixedMethodCall
 *     The resource client is driven through its magic verb properties.
 */
final class CallerTest extends TestCase
{
    use RemovesDirectoryTree;

    private string $tmpDir = '';

    protected function setUp(): void
    {
        $this->tmpDir = sys_get_temp_dir() . '/' . uniqid('bear-es-caller-', true);
        mkdir($this->tmpDir);
    }

    protected function tearDown(): void
    {
        self::removeTree($this->tmpDir);
    }

    public function testPointsAtTheEntryPointTheOnGetTheServiceAndTheTemplate(): void
    {
        $resource = $this->resource(new DevLogModule(
            bodyDir: $this->tmpDir . '/bodies',
            logger: $logger = new SemanticLogger(),
            module: new CallerAppModule(),
        ));

        $page = $resource->get('page://self/index');
        $page->toString();

        $callers = self::callersByUri(self::flushedRoots($logger));
        $this->assertSame([
            'page://self/index' => self::at(__FILE__, "get('page://self/index')", __METHOD__),
            'app://self/nav' => self::at(self::fileOf(Index::class), "get('app://self/nav')", Index::class . '::onGet'),
            'app://self/setting' => self::at(
                self::fileOf(SiteSetting::class),
                "get('app://self/setting')",
                SiteSetting::class . '::name',
            ),
            'app://self/child' => self::at(
                dirname(self::fileOf(CallerAppModule::class)) . '/template/Index.php',
                '$child->body',
            ),
        ], $callers);
    }

    public function testAWovenMethodIsReadAsTheMethodItOverrides(): void
    {
        $resource = $this->resource(new DevLogModule(
            bodyDir: $this->tmpDir . '/bodies',
            logger: $logger = new SemanticLogger(),
            module: new UnifiedLogModule($logger, $this->tmpDir),
        ));

        $resource->get('app://self/profile', ['name' => 'koriym']);

        $onGet = new ReflectionMethod(Profile::class, 'onGet');
        $callers = self::callersByUri(self::flushedRoots($logger));
        $this->assertSame(
            [
                'file' => $onGet->getFileName(),
                'line' => $onGet->getStartLine(),
                'function' => Profile::class . '::onGet',
            ],
            $callers['app://self/greeting'],
        );
    }

    public function testSkipsAComposerPackageInstalledThroughASymlink(): void
    {
        mkdir($this->tmpDir . '/linked-lib');
        mkdir($this->tmpDir . '/vendor/acme', 0777, true);
        symlink($this->tmpDir . '/linked-lib', $this->tmpDir . '/vendor/acme/lib');
        file_put_contents(
            $this->tmpDir . '/linked-lib/nav.php',
            '<?php return static fn (BEAR\\Resource\\ResourceInterface $r) => $r->get(\'app://self/nav\');',
        );
        $installed = InstalledVersions::getAllRawData()[0];
        InstalledVersions::reload([
            'root' => $installed['root'],
            'versions' => [
                'acme/lib' => ['install_path' => $this->tmpDir . '/vendor/acme/lib', 'dev_requirement' => false],
            ],
        ]);

        try {
            $resource = $this->resource(new DevLogModule(
                bodyDir: $this->tmpDir . '/bodies',
                logger: $logger = new SemanticLogger(),
                module: new CallerAppModule(),
            ));
            /**
             * @var Closure(ResourceInterface): mixed $getNav
             * @psalm-suppress UnresolvableInclude
             */
            $getNav = require $this->tmpDir . '/vendor/acme/lib/nav.php';
            $getNav($resource);
        } finally {
            InstalledVersions::reload($installed);
        }

        $this->assertSame(
            ['app://self/nav' => self::at(__FILE__, '$getNav($resource)', __METHOD__)],
            self::callersByUri(self::flushedRoots($logger)),
        );
    }

    public function testTheProductionModuleRecordsNoCaller(): void
    {
        $resource = $this->resource(new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            logger: $logger = new SemanticLogger(),
            module: new CallerAppModule(),
        ));

        $page = $resource->get('page://self/index');
        $page->toString();

        $this->assertSame(
            [
                'page://self/index' => null,
                'app://self/nav' => null,
                'app://self/setting' => null,
                'app://self/child' => null,
            ],
            self::callersByUri(self::flushedRoots($logger)),
        );
    }

    public function testTheCallerLeavesTheEventAlone(): void
    {
        $withoutCaller = self::extractOrder(null);

        $this->assertCount(1, $withoutCaller);
        $this->assertEquals(
            $withoutCaller,
            self::extractOrder(['file' => __FILE__, 'line' => __LINE__, 'function' => __METHOD__]),
        );
    }

    /**
     * @param array{file: string, line: int, function?: string}|null $caller
     *
     * @return array<array-key, mixed>
     */
    private static function extractOrder(array|null $caller): array
    {
        $logger = new SemanticLogger();
        $logger->close(ResourceResponseContext::success(201), $logger->open(new ResourceRequestContext(
            uri: 'app://self/orders',
            method: 'POST',
            params: ['id' => 1],
            timestamp: '2026-11-10T12:00:00.000000+00:00',
            caller: $caller,
        )));

        return iterator_to_array((new SemanticLogExtractor())->extract($logger->flush()));
    }

    private function resource(AbstractModule $module): ResourceInterface
    {
        return (new Injector($module, $this->tmpDir))->getInstance(ResourceInterface::class);
    }

    /** @param class-string $class */
    private static function fileOf(string $class): string
    {
        return (string) (new ReflectionClass($class))->getFileName();
    }

    /** @return array{file: string, line: int, function?: string} the first line of $file that holds $code */
    private static function at(string $file, string $code, string|null $function = null): array
    {
        $line = 0;
        foreach ((array) file($file) as $i => $text) {
            if (str_contains((string) $text, $code)) {
                $line = $i + 1;
                break;
            }
        }

        $caller = ['file' => $file, 'line' => $line];
        if ($function !== null) {
            $caller['function'] = $function;
        }

        return $caller;
    }

    /**
     * Every resource_request in the tree, keyed by uri.
     *
     * @param list<array<string, mixed>> $entries
     *
     * @return array<string, mixed>
     */
    private static function callersByUri(array $entries): array
    {
        $callers = [];
        foreach ($entries as $entry) {
            if ($entry['type'] === 'resource_request') {
                $callers[(string) $entry['context']['uri']] = $entry['context']['caller'] ?? null;
            }

            $callers += self::callersByUri($entry['log'] ?? []);
        }

        return $callers;
    }

    /** @return list<array<string, mixed>> */
    private static function flushedRoots(SemanticLogger $logger): array
    {
        /** @var array{log: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $tree['log'];
    }
}
