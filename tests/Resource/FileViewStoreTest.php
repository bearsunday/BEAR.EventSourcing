<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\FileViewStore;
use BEAR\EventSourcing\Resource\ViewStoreException;
use BEAR\Resource\Method;
use BEAR\Resource\Request;
use PHPUnit\Framework\TestCase;

use function file_exists;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;

final class FileViewStoreTest extends TestCase
{
    public function testStoresRenderedViewAndReturnsFileRef(): void
    {
        $dir = self::newViewDir();
        $store = new FileViewStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $viewRef = $store($request, $ro);

        $this->assertSame('file://' . $dir . '/000001.json', $viewRef);
        $this->assertSame('{"id":1}', file_get_contents($dir . '/000001.json'));

        FileViewStore::clearDirectory($dir);
        rmdir($dir);
    }

    public function testStoresSequentialFiles(): void
    {
        $dir = self::newViewDir();
        $store = new FileViewStore($dir);
        $ro = new FakeResourceObject(body: ['id' => 1]);
        $request = new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::GET,
        );

        $this->assertSame('file://' . $dir . '/000001.json', $store($request, $ro));
        $this->assertSame('file://' . $dir . '/000002.json', $store($request, $ro));

        FileViewStore::clearDirectory($dir);
        rmdir($dir);
    }

    public function testClearDirectoryRemovesPreviousRunFiles(): void
    {
        $dir = self::newViewDir();
        mkdir($dir . '/nested');
        file_put_contents($dir . '/old.json', '{}');
        file_put_contents($dir . '/nested/old.json', '{}');

        FileViewStore::clearDirectory($dir);

        $this->assertTrue(is_dir($dir));
        $this->assertFalse(file_exists($dir . '/old.json'));
        $this->assertFalse(file_exists($dir . '/nested'));

        rmdir($dir);
    }

    public function testRejectsUnsafeDirectory(): void
    {
        $this->expectException(ViewStoreException::class);

        FileViewStore::clearDirectory('');
    }

    public function testRejectsFilePath(): void
    {
        $dir = self::newViewDir();
        $file = $dir . '/not-directory';
        file_put_contents($file, '');

        try {
            $this->expectException(ViewStoreException::class);
            FileViewStore::clearDirectory($file);
        } finally {
            FileViewStore::clearDirectory($dir);
            rmdir($dir);
        }
    }

    private static function newViewDir(): string
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-views-', true);
        mkdir($dir);

        return $dir;
    }
}
