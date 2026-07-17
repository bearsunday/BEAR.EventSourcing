<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\Resource\JsonRenderer;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use PHPUnit\Framework\TestCase;
use RuntimeException;

use function chmod;
use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function getmyuid;
use function is_dir;
use function mkdir;
use function restore_error_handler;
use function rmdir;
use function set_error_handler;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

use const E_WARNING;

final class FileBodyStoreTest extends TestCase
{
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

        $this->assertSame('file://' . $dir . '/000001.json', $bodyRef);
        $this->assertSame('{"id":1}', file_get_contents($dir . '/000001.json'));

        FileBodyStore::clearDirectory($dir);
        rmdir($dir);
    }

    public function testStoresSequentialFiles(): void
    {
        $dir = self::newBodyDir();
        $store = new FileBodyStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $this->assertSame('file://' . $dir . '/000001.json', $store($request, $ro));
        $this->assertSame('file://' . $dir . '/000002.json', $store($request, $ro));

        FileBodyStore::clearDirectory($dir);
        rmdir($dir);
    }

    public function testClearDirectoryRemovesPreviousRunFiles(): void
    {
        $dir = self::newBodyDir();
        new FileBodyStore($dir); // a prior run adopts the directory (writes the ownership marker)
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
            $this->fail('Expected a BodyStoreException for an unowned directory.');
        } catch (BodyStoreException) {
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
        new FileBodyStore($dir); // adopts the directory (writes the ownership marker)
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
            // (string) $ro would have swallowed this and written an empty file behind a valid ref.
            $this->assertFalse(file_exists($dir . '/000001.json'), 'a failed render must not leave a file');
        } finally {
            FileBodyStore::clearDirectory($dir);
            rmdir($dir);
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

        FileBodyStore::clearDirectory($dir);
        rmdir($dir);
    }

    private static function newBodyDir(): string
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-bodies-', true);
        mkdir($dir);

        return $dir;
    }
}
