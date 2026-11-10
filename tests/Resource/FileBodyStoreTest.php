<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\EventSourcing\Tests\Fixture\Body\EntryList;
use BEAR\EventSourcing\Tests\Fixture\Body\Kind;
use BEAR\EventSourcing\Tests\Fixture\Body\Pager;
use BEAR\EventSourcing\Tests\Fixture\Body\Serialized;
use BEAR\EventSourcing\Tests\RemovesDirectoryTree;
use BEAR\Resource\HalLinker;
use BEAR\Resource\HalRenderer;
use BEAR\Resource\Method;
use BEAR\Resource\NullReverseLinker;
use BEAR\Resource\Request;
use Composer\InstalledVersions;
use JsonSerializable;
use PHPUnit\Framework\Attributes\RequiresPhp;
use PHPUnit\Framework\TestCase;
use ReflectionClass;
use stdClass;

use function assert;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function filesize;
use function getmyuid;
use function is_dir;
use function mkdir;
use function range;
use function restore_error_handler;
use function rmdir;
use function scandir;
use function set_error_handler;
use function sort;
use function str_ends_with;
use function str_contains;
use function str_replace;
use function str_starts_with;
use function strlen;
use function substr;
use function symlink;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;

use const E_USER_WARNING;
use const NAN;

final class FileBodyStoreTest extends TestCase
{
    use RemovesDirectoryTree;

    public function testStoresBodyAndReturnsFileRef(): void
    {
        $dir = self::newBodyDir();
        $store = new FileBodyStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $bodyRef = $store($request, $ro);

        $this->assertNotNull($bodyRef);
        $this->assertTrue(str_starts_with($bodyRef, 'file://' . $dir . '/'));
        $this->assertTrue(str_ends_with($bodyRef, '/000001.json'));
        $this->assertSame('{"id":1}', file_get_contents(self::pathFromRef($bodyRef)));

        self::removeTree($dir);
    }

    public function testStoresSequentialFilesWithinOneGeneration(): void
    {
        $dir = self::newBodyDir();
        $store = new FileBodyStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $first = $store($request, $ro);
        $second = $store($request, $ro);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertTrue(str_ends_with($first, '/000001.json'));
        $this->assertTrue(str_ends_with($second, '/000002.json'));
        // Same generation directory: both files sit next to each other.
        $this->assertSame(dirname(self::pathFromRef($first)), dirname(self::pathFromRef($second)));

        self::removeTree($dir);
    }

    public function testStoresTheBodyWithoutTheRendererOrTouchingTheResponse(): void
    {
        $dir = self::newBodyDir();
        $body = new stdClass();
        $body->id = 1;
        $ro = new FakeResourceObject(body: $body);
        $ro->setRenderer(new HalRenderer(new HalLinker(new NullReverseLinker())));
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $bodyRef = (new FileBodyStore($dir))($request, $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame('{"id":1}', file_get_contents(self::pathFromRef($bodyRef)));
        $this->assertSame($body, $ro->body);
        $this->assertArrayNotHasKey('Content-Type', $ro->headers);

        self::removeTree($dir);
    }

    public function testStoresAnUnrunRequestAsARequestMarkerWithoutRunningIt(): void
    {
        $dir = self::newBodyDir();
        $runs = 0;
        $child = new FakeResourceObject('app://self/child?ignored=1');
        $lazy = new Request(
            new CallbackInvoker(static function () use (&$runs, $child): FakeResourceObject {
                $runs++;

                return $child;
            }),
            $child,
            Method::GET,
            ['id' => 'lazy'],
        );
        $nested = new FakeResourceObject('app://self/nav', ['items' => ['a', 'b']]);
        $ro = new FakeResourceObject('page://self/index', ['title' => 'index', 'nav' => $nested, 'child' => $lazy]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $bodyRef = (new FileBodyStore($dir))($request, $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame(0, $runs);
        $this->assertSame(
            '{"title":"index","nav":{"items":["a","b"]},'
            . '"child":{"$request":{"method":"GET","uri":"app://self/child","params":{"id":"lazy"}}}}',
            file_get_contents(self::pathFromRef($bodyRef)),
        );
        $this->assertSame(['title' => 'index', 'nav' => $nested, 'child' => $lazy], $ro->body);

        self::removeTree($dir);
    }

    public function testStoresTheParamsOfAnUnrunRequestTheWayItStoresABody(): void
    {
        $dir = self::newBodyDir();
        $child = new FakeResourceObject('app://self/child');
        $lazy = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $child),
            $child,
            Method::GET,
            ['kind' => Kind::News],
        );
        $ro = new FakeResourceObject('page://self/index', ['child' => $lazy]);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame(
            '{"child":{"$request":{"method":"GET","uri":"app://self/child","params":{"kind":"News"}}}}',
            file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testStoresTheNonPublicStateOfAnApplicationObject(): void
    {
        $dir = self::newBodyDir();
        $runs = 0;
        $child = new FakeResourceObject('app://self/child');
        $lazy = new Request(
            new CallbackInvoker(static function () use (&$runs, $child): FakeResourceObject {
                $runs++;

                return $child;
            }),
            $child,
            Method::GET,
        );
        $ro = new FakeResourceObject(body: [
            'list' => new EntryList([['id' => 1], $lazy], new Pager(1, 3)),
            'serialized' => new Serialized(['child' => $lazy, 'kind' => Kind::News]),
        ]);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame(0, $runs);
        $marker = '{"$request":{"method":"GET","uri":"app://self/child","params":[]}}';
        $this->assertSame(
            '{"list":{"previous":null,"items":[{"id":1},' . $marker . '],'
            . '"pager":{"current":1,"last":3},"service":null},"serialized":{"child":' . $marker . ',"kind":"News"}}',
            file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testNamesTheClassOfAnObjectItDoesNotOpenOrHasAlreadyEntered(): void
    {
        $dir = self::newBodyDir();
        $list = new EntryList([], new Pager(1, 1), new HalLinker(new NullReverseLinker()));
        $list->previous = $list;
        $ro = new FakeResourceObject(body: ['list' => $list, 'callback' => static fn (): int => 1]);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame(
            '{"list":{"previous":{"$object":"' . self::jsonClass(EntryList::class) . '"},"items":[],'
            . '"pager":{"current":1,"last":1},"service":{"$object":"' . self::jsonClass(HalLinker::class) . '"}},'
            . '"callback":{"$object":"Closure"}}',
            file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    /** @psalm-suppress UndefinedMethod newLazyGhost() is PHP 8.4 */
    #[RequiresPhp('>= 8.4')]
    public function testReadsAPackageObjectWithoutRunningItsHooksOrInitializingIt(): void
    {
        $dir = self::newBodyDir();
        $class = 'AcmeHooked\\Hooked';
        $packageDir = $dir . '/vendor/acme/hooked';
        mkdir($packageDir, 0777, true);
        file_put_contents($packageDir . '/Hooked.php', <<<'PHP'
            <?php
            namespace AcmeHooked;
            final class Hooked
            {
                public int $count { get { throw new \LogicException('hook ran'); } set => $value; }
                private string $secret = 'hidden';
                public function __construct() { $this->count = 1; }
            }
            PHP);
        $installed = InstalledVersions::getAllRawData()[0];
        InstalledVersions::reload([
            'root' => $installed['root'],
            'versions' => ['acme/hooked' => ['install_path' => $packageDir, 'dev_requirement' => false]],
        ]);
        try {
            /** @psalm-suppress UnresolvableInclude */
            require $packageDir . '/Hooked.php';
            $store = new FileBodyStore($dir . '/bodies');
        } finally {
            InstalledVersions::reload($installed);
        }

        $initialized = 0;
        /** @var class-string $class */
        $reflection = new ReflectionClass($class);
        /** @var object $ghost */
        $ghost = $reflection->newLazyGhost(static function () use (&$initialized): void {
            $initialized++;
        });
        $ro = new FakeResourceObject(body: ['hooked' => $reflection->newInstance(), 'ghost' => $ghost]);

        $bodyRef = $store(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame(0, $initialized);
        $this->assertSame(
            '{"hooked":{"count":1},"ghost":{"$object":"AcmeHooked\\\\Hooked"}}',
            file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testCutsASelfReferencingArrayAtADepth(): void
    {
        $dir = self::newBodyDir();
        $loop = ['id' => 1];
        $loop['self'] = &$loop;
        $ro = new FakeResourceObject(body: $loop);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertStringContainsString(
            '"self":{"$truncated":"depth"}',
            (string) file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testStopsAGraphThatSharesOneChildManyTimes(): void
    {
        $dir = self::newBodyDir();
        $node = ['leaf' => 1];
        for ($i = 0; $i < 20; $i++) {
            $node = ['left' => $node, 'right' => $node];
        }

        $ro = new FakeResourceObject(body: $node);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $file = self::pathFromRef($bodyRef);
        $this->assertLessThan(2_000_000, filesize($file));
        $this->assertTrue(str_contains((string) file_get_contents($file), '{"$truncated":"size"}'));

        self::removeTree($dir);
    }

    public function testKeepsAChainOfObjectsAsDeepAsItsJson(): void
    {
        $dir = self::newBodyDir();
        $node = (object) ['id' => 'innermost'];
        for ($i = 0; $i < 59; $i++) {
            $node = (object) ['next' => $node];
        }

        $ro = new FakeResourceObject(body: ['chain' => $node]);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $stored = (string) file_get_contents(self::pathFromRef($bodyRef));
        $this->assertStringContainsString('"id":"innermost"', $stored);
        $this->assertStringNotContainsString('$truncated', $stored);

        self::removeTree($dir);
    }

    public function testStopsAJsonSerializeThatReturnsANewInstanceEachTime(): void
    {
        $dir = self::newBodyDir();
        $endless = new class implements JsonSerializable {
            public function jsonSerialize(): mixed
            {
                return new self();
            }
        };
        $ro = new FakeResourceObject(body: ['endless' => $endless]);

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertStringContainsString(
            '{"$truncated":"depth"}',
            (string) file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testStopsAChainOfResourceObjectBodiesAtTheDepthLimit(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 'innermost']);
        for ($i = 0; $i < 3000; $i++) {
            $ro = new FakeResourceObject(body: $ro);
        }

        $bodyRef = (new FileBodyStore($dir))(self::requestOf($ro), $ro);

        $this->assertNotNull($bodyRef);
        $this->assertStringContainsString(
            '{"$truncated":"depth"}',
            (string) file_get_contents(self::pathFromRef($bodyRef)),
        );

        self::removeTree($dir);
    }

    public function testDirectoryCreationIsDeferredUntilFirstStore(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-bodies-', true);
        $store = new FileBodyStore($dir);

        $this->assertFalse(is_dir($dir), 'construction alone must not touch the filesystem');

        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );
        $store($request, $ro);

        $this->assertTrue(is_dir($dir));
        $generations = self::subdirectories($dir);
        $this->assertCount(1, $generations);
        $this->assertTrue(file_exists($dir . '/' . $generations[0] . '/' . FileBodyStore::MARKER));

        self::removeTree($dir);
    }

    public function testRejectsNonPositiveKeep(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-bodies-', true);

        try {
            $this->expectException(BodyStoreException::class);
            new FileBodyStore($dir, keep: 0);
        } finally {
            $this->assertFalse(is_dir($dir), 'a rejected keep must not touch the filesystem');
        }
    }

    public function testTwoInstancesShareRootWithDistinctGenerations(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $first = (new FileBodyStore($dir))($request, $ro);
        usleep(1_000);
        $second = (new FileBodyStore($dir))($request, $ro);

        $this->assertNotNull($first);
        $this->assertNotNull($second);
        $this->assertNotSame(
            dirname(self::pathFromRef($first)),
            dirname(self::pathFromRef($second)),
            'two sessions on the same root must not collide on one generation',
        );
        $this->assertTrue(str_ends_with($first, '/000001.json'));
        $this->assertTrue(str_ends_with($second, '/000001.json'), 'each session starts its own sequence at 1');

        self::removeTree($dir);
    }

    public function testRetentionPrunesOldestGenerationsFirst(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $refs = [];
        foreach (range(1, 3) as $_) {
            $refs[] = (new FileBodyStore($dir, keep: 2))($request, $ro);
            usleep(1_000);
        }

        assert($refs[0] !== null && $refs[1] !== null && $refs[2] !== null);

        $generations = self::subdirectories($dir);
        $this->assertCount(2, $generations, 'only $keep generations survive');

        $secondGeneration = dirname(self::pathFromRef($refs[1]));
        $thirdGeneration = dirname(self::pathFromRef($refs[2]));
        $survivors = [$dir . '/' . $generations[0], $dir . '/' . $generations[1]];
        $this->assertContains($secondGeneration, $survivors);
        $this->assertContains($thirdGeneration, $survivors);
        $this->assertFalse(is_dir(dirname(self::pathFromRef($refs[0]))), 'the oldest generation is pruned');

        self::removeTree($dir);
    }

    public function testKeepOneRetainsOnlyTheNewestGeneration(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $first = (new FileBodyStore($dir, keep: 1))($request, $ro);
        usleep(1_000);
        $second = (new FileBodyStore($dir, keep: 1))($request, $ro);

        assert($first !== null && $second !== null);
        $this->assertCount(1, self::subdirectories($dir), 'keep: 1 retains a single generation');
        $this->assertFalse(is_dir(dirname(self::pathFromRef($first))), 'the first generation is pruned');
        $this->assertTrue(is_dir(dirname(self::pathFromRef($second))), 'the newest generation survives');

        self::removeTree($dir);
    }

    public function testPruningRunsOnlyOnceWhenAGenerationIsCreated(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $store = new FileBodyStore($dir, keep: 1);
        $store($request, $ro); // creates this instance's generation; prune runs once, here

        // A generation that sorts before any real one and would be evicted by a
        // fresh prune pass (keep: 1, two owned generations, oldest-first).
        $stale = $dir . '/00000101-000000-000000-00000000';
        mkdir($stale);
        file_put_contents($stale . '/' . FileBodyStore::MARKER, '');

        $store($request, $ro); // second store on the *same* instance

        $this->assertTrue(is_dir($stale), 'a store on an existing instance must not prune again');

        unlink($stale . '/' . FileBodyStore::MARKER);
        rmdir($stale);
        self::removeTree($dir);
    }

    public function testAGenerationThatCannotBePrunedWarnsAndTheWriteStillSucceeds(): void
    {
        if (getmyuid() === 0) {
            $this->markTestSkipped('Cannot simulate an unwritable directory as root.');
        }

        $dir = self::newBodyDir();
        $stuck = $dir . '/00000101-000000-000000-00000000';
        mkdir($stuck);
        file_put_contents($stuck . '/' . FileBodyStore::MARKER, '');
        mkdir($stuck . '/locked');
        file_put_contents($stuck . '/locked/file', '{}');
        chmod($stuck . '/locked', 0500);

        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $userWarnings = 0;
        set_error_handler(static function (int $errno) use (&$userWarnings): bool {
            if ($errno === E_USER_WARNING) {
                $userWarnings++;
            }

            return true;
        });
        try {
            $first = (new FileBodyStore($dir, keep: 1))($request, $ro);
            usleep(1_000);
            $second = (new FileBodyStore($dir, keep: 1))($request, $ro);
        } finally {
            restore_error_handler();
            chmod($stuck . '/locked', 0700);
        }

        assert($first !== null && $second !== null);
        $this->assertSame(2, $userWarnings, 'each prune pass warns about the stuck generation');
        $this->assertTrue(file_exists(self::pathFromRef($second)), 'a stuck generation never blocks a write');
        $this->assertTrue(
            file_exists($stuck . '/' . FileBodyStore::MARKER),
            'a prune that fails partway leaves the generation marked for the next one',
        );

        (new FileBodyStore($dir, keep: 1))($request, $ro);
        $this->assertDirectoryDoesNotExist($stuck, 'the marked generation is pruned once it can be');

        self::removeTree($dir);
    }

    public function testSymlinkedEntryUnderRootIsNotTreatedAsAGeneration(): void
    {
        $dir = self::newBodyDir();
        $target = self::newBodyDir();
        file_put_contents($target . '/' . FileBodyStore::MARKER, '');
        file_put_contents($target . '/precious.json', '{}');
        symlink($target, $dir . '/00000101-000000-000000-00000000');

        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );
        (new FileBodyStore($dir, keep: 1))($request, $ro);

        $this->assertTrue(file_exists($target . '/precious.json'), 'pruning never follows a symlink');

        unlink($dir . '/00000101-000000-000000-00000000');
        self::removeTree($dir);
        self::removeTree($target);
    }

    public function testTheNewGenerationSurvivesWhenTheClockRunsBehind(): void
    {
        $dir = self::newBodyDir();
        foreach (['99991231-235958-000000-00000000', '99991231-235959-000000-00000000'] as $future) {
            mkdir($dir . '/' . $future);
            file_put_contents($dir . '/' . $future . '/' . FileBodyStore::MARKER, '');
        }

        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );
        $ref = (new FileBodyStore($dir, keep: 2))($request, $ro);

        assert($ref !== null);
        $this->assertTrue(file_exists(self::pathFromRef($ref)), 'the generation being written is never pruned');
        $this->assertCount(2, self::subdirectories($dir));
        $this->assertFalse(is_dir($dir . '/99991231-235958-000000-00000000'), 'the oldest other generation goes');

        self::removeTree($dir);
    }

    public function testForeignDirectoryUnderRootIsNeverPrunedOrCounted(): void
    {
        $dir = self::newBodyDir();
        // Sorts after every generation name (letters follow digits in ASCII), the
        // exact shape that used to inflate the BeMart overflow count.
        mkdir($dir . '/zzz-foreign');
        file_put_contents($dir . '/zzz-foreign/keep-me.txt', 'not ours');

        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );
        foreach (range(1, 3) as $_) {
            (new FileBodyStore($dir, keep: 2))($request, $ro);
            usleep(1_000);
        }

        $this->assertTrue(is_dir($dir . '/zzz-foreign'), 'a directory without the marker is never pruned');
        $this->assertTrue(file_exists($dir . '/zzz-foreign/keep-me.txt'));

        $owned = [];
        foreach (self::subdirectories($dir) as $name) {
            if (file_exists($dir . '/' . $name . '/' . FileBodyStore::MARKER)) {
                $owned[] = $name;
            }
        }

        // With the foreign directory wrongly counted toward $keep (the BeMart bug this
        // guards against), only one owned generation would survive instead of two.
        $this->assertCount(2, $owned, 'the foreign directory is not counted toward $keep');

        unlink($dir . '/zzz-foreign/keep-me.txt');
        rmdir($dir . '/zzz-foreign');
        self::removeTree($dir);
    }

    public function testRejectsUnsafeDirectory(): void
    {
        $this->expectException(BodyStoreException::class);

        new FileBodyStore('');
    }

    public function testRejectsFilePath(): void
    {
        $dir = self::newBodyDir();
        $file = $dir . '/not-directory';
        file_put_contents($file, '');
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        try {
            $this->expectException(BodyStoreException::class);
            (new FileBodyStore($file))($request, $ro);
        } finally {
            unlink($file);
            rmdir($dir);
        }
    }

    public function testRejectsDotSegments(): void
    {
        $this->expectException(BodyStoreException::class);

        new FileBodyStore(sys_get_temp_dir() . '/..');
    }

    public function testRejectsPathResolvingToRoot(): void
    {
        $this->expectException(BodyStoreException::class);

        new FileBodyStore('//');
    }

    public function testNeverCallsTheRenderer(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $ro->setRenderer(new ThrowingRenderer());
        $priorView = $ro->view;
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::POST,
        );

        $bodyRef = (new FileBodyStore($dir))($request, $ro);

        $this->assertNotNull($bodyRef);
        $this->assertSame('{"id":1}', file_get_contents(self::pathFromRef($bodyRef)));
        $this->assertSame($priorView, $ro->view);

        self::removeTree($dir);
    }

    public function testUnencodableBodyThrowsAndCreatesNoGeneration(): void
    {
        $dir = self::newBodyDir();
        $ro = new FakeResourceObject(body: ['ratio' => NAN]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        try {
            (new FileBodyStore($dir))($request, $ro);
            $this->fail('Expected a BodyStoreException.');
        } catch (BodyStoreException) {
            $this->assertSame([], self::subdirectories($dir));
        } finally {
            self::removeTree($dir);
        }
    }

    private static function requestOf(FakeResourceObject $ro): Request
    {
        return new Request(new CallbackInvoker(static fn (): FakeResourceObject => $ro), $ro, Method::GET);
    }

    /** @param class-string $class */
    private static function jsonClass(string $class): string
    {
        return str_replace('\\', '\\\\', $class);
    }

    private static function newBodyDir(): string
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-bodies-', true);
        mkdir($dir);

        return $dir;
    }

    private static function pathFromRef(string $bodyRef): string
    {
        return substr($bodyRef, strlen('file://'));
    }

    /** @return list<string> */
    private static function subdirectories(string $dir): array
    {
        $entries = scandir($dir);
        if ($entries === false) {
            $entries = [];
        }

        $names = [];
        foreach ($entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            if (is_dir($dir . '/' . $entry)) {
                $names[] = $entry;
            }
        }

        sort($names);

        return $names;
    }
}
