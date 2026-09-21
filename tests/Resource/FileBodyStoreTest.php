<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\EventSourcing\Resource\UnownedDirectoryException;
use BEAR\EventSourcing\Tests\RemovesDirectoryTree;
use BEAR\Resource\JsonRenderer;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function assert;
use function chmod;
use function dirname;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
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
use function str_starts_with;
use function strlen;
use function substr;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;
use function usleep;

use const E_WARNING;

final class FileBodyStoreTest extends TestCase
{
    use RemovesDirectoryTree;

    public function testStoresRenderedBodyAndReturnsFileRef(): void
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

    public function testClearDirectoryRemovesPreviousRunFiles(): void
    {
        $dir = self::newBodyDir();
        file_put_contents($dir . '/' . FileBodyStore::MARKER, '');
        mkdir($dir . '/nested');
        file_put_contents($dir . '/old.json', '{}');
        file_put_contents($dir . '/nested/old.json', '{}');

        FileBodyStore::clearDirectory($dir);

        $this->assertTrue(is_dir($dir));
        $this->assertFalse(file_exists($dir . '/old.json'));
        $this->assertFalse(file_exists($dir . '/nested'));

        rmdir($dir);
    }

    public function testRefusesToClearADirectoryItDoesNotOwn(): void
    {
        $dir = self::newBodyDir();
        $precious = $dir . '/precious.txt';
        file_put_contents($precious, 'keep me');

        try {
            FileBodyStore::clearDirectory($dir);
            $this->fail('Expected an UnownedDirectoryException for an unowned directory.');
        } catch (UnownedDirectoryException) {
            $this->assertTrue(file_exists($precious), 'unmarked contents must be left untouched');
        } finally {
            unlink($precious);
            rmdir($dir);
        }
    }

    public function testKeepsOwnershipMarkerWhenCleanupFailsPartway(): void
    {
        if (getmyuid() === 0) {
            $this->markTestSkipped('Cannot simulate an unwritable directory as root.');
        }

        $dir = self::newBodyDir();
        file_put_contents($dir . '/' . FileBodyStore::MARKER, '');
        mkdir($dir . '/locked');
        file_put_contents($dir . '/locked/file', '{}');
        chmod($dir . '/locked', 0500); // read+execute only: unlink of its child fails

        set_error_handler(static fn (): bool => true, E_WARNING); // swallow the expected unlink() warning
        try {
            FileBodyStore::clearDirectory($dir);
            $this->fail('Expected cleanup to fail on the unwritable directory.');
        } catch (BodyStoreException) {
            // The marker must survive the partial failure so a retry can still own and clear the directory.
        } finally {
            restore_error_handler();
        }

        chmod($dir . '/locked', 0700);
        FileBodyStore::clearDirectory($dir); // succeeds only if the marker survived (assertOwned passes)

        $this->assertTrue(is_dir($dir));
        rmdir($dir);
    }

    public function testRejectsUnsafeDirectory(): void
    {
        $this->expectException(BodyStoreException::class);

        FileBodyStore::clearDirectory('');
    }

    public function testRejectsFilePath(): void
    {
        $dir = self::newBodyDir();
        $file = $dir . '/not-directory';
        file_put_contents($file, '');

        try {
            $this->expectException(BodyStoreException::class);
            FileBodyStore::clearDirectory($file);
        } finally {
            unlink($file);
            rmdir($dir);
        }
    }

    public function testRejectsDotSegments(): void
    {
        $this->expectException(BodyStoreException::class);

        FileBodyStore::clearDirectory(sys_get_temp_dir() . '/..');
    }

    public function testRejectsPathResolvingToRoot(): void
    {
        $this->expectException(BodyStoreException::class);

        FileBodyStore::clearDirectory('//');
    }

    public function testRenderFailurePropagatesAndWritesNoEmptyFile(): void
    {
        $dir = self::newBodyDir();
        $store = new FileBodyStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $ro->setRenderer(new ThrowingRenderer());
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::POST,
        );

        try {
            $store($request, $ro);
            $this->fail('Expected the render exception to propagate.');
        } catch (RuntimeException) {
            $this->assertSame(
                [],
                self::subdirectories($dir),
                'a failed render must create no generation directory (costs nothing until a body is stored)',
            );
        } finally {
            self::removeTree($dir);
        }
    }

    public function testObservationRestoresTheResponseView(): void
    {
        $dir = self::newBodyDir();
        $store = new FileBodyStore($dir);
        $ro = new FakeResourceObject(body: ['name' => 'before']);
        $ro->setRenderer(new JsonRenderer());
        $priorView = $ro->view;
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::POST,
        );

        $bodyRef = $store($request, $ro);

        // Rendering the body for storage must not cache the view: it is restored to
        // its prior value so later stages still render the current body.
        $this->assertNotNull($bodyRef);
        $this->assertSame($priorView, $ro->view);

        self::removeTree($dir);
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
