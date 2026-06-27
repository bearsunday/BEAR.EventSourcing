<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\BodyStoreException;
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
        mkdir($dir . '/nested');
        file_put_contents($dir . '/old.json', '{}');
        file_put_contents($dir . '/nested/old.json', '{}');

        FileBodyStore::clearDirectory($dir);

        $this->assertTrue(is_dir($dir));
        $this->assertFalse(file_exists($dir . '/old.json'));
        $this->assertFalse(file_exists($dir . '/nested'));

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
            FileBodyStore::clearDirectory($dir);
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

    private static function newBodyDir(): string
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-bodies-', true);
        mkdir($dir);

        return $dir;
    }
}
