<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function explode;
use function file_put_contents;
use function is_dir;
use function is_file;
use function is_link;
use function mkdir;
use function realpath;
use function rmdir;
use function sprintf;
use function str_starts_with;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;

final class FileViewStore implements ViewStoreInterface
{
    private int $sequence = 0;

    public function __construct(
        private readonly string $dir,
    ) {
        self::ensureDirectory($dir);
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . sprintf('%06d.json', ++$this->sequence);
        $bytes = file_put_contents($file, (string) $ro, LOCK_EX);
        if ($bytes === false) {
            throw new ViewStoreException(sprintf('Failed to write view file: %s', $file));
        }

        return 'file://' . $file;
    }

    public static function clearDirectory(string $dir): void
    {
        self::ensureDirectory($dir);
        self::clearContents($dir);
    }

    private static function ensureDirectory(string $dir): void
    {
        self::assertSafeDirectory($dir);
        if (is_dir($dir)) {
            return;
        }

        if (is_file($dir) || is_link($dir)) {
            throw new ViewStoreException(sprintf('View store path is not a directory: %s', $dir));
        }

        if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new ViewStoreException(sprintf('Failed to create view store directory: %s', $dir));
        }
    }

    private static function assertSafeDirectory(string $dir): void
    {
        $trimmed = trim($dir);
        if ($trimmed === '' || $trimmed === DIRECTORY_SEPARATOR) {
            throw new ViewStoreException('Unsafe view store directory.');
        }

        if (! str_starts_with($dir, DIRECTORY_SEPARATOR)) {
            throw new ViewStoreException(sprintf('View store directory must be absolute: %s', $dir));
        }

        foreach (explode(DIRECTORY_SEPARATOR, $dir) as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new ViewStoreException(sprintf('View store directory must not contain dot segments: %s', $dir));
            }
        }

        if (is_link($dir)) {
            throw new ViewStoreException(sprintf('View store directory must not be a symlink: %s', $dir));
        }

        if (realpath($dir) === DIRECTORY_SEPARATOR) {
            throw new ViewStoreException('Unsafe view store directory.');
        }
    }

    private static function clearContents(string $dir): void
    {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @psalm-suppress MixedAssignment SPL recursive iterator yields SplFileInfo. */
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPathname();
            if ($file->isLink() || $file->isFile()) {
                if (! unlink($path)) {
                    throw new ViewStoreException(sprintf('Failed to remove view store file: %s', $path));
                }

                continue;
            }

            if ($file->isDir() && ! rmdir($path)) {
                throw new ViewStoreException(sprintf('Failed to remove view store directory: %s', $path));
            }
        }
    }
}
