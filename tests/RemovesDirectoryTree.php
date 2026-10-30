<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function is_dir;
use function rmdir;
use function unlink;

/**
 * FileBodyStore's own directory is now a root shared across generation
 * subdirectories it owns individually, not a single directory the store
 * itself owns end to end — so tests that hand it a fresh root tear the whole
 * tree down themselves instead of asking `FileBodyStore::clearDirectory()`
 * to clear a directory it was never marked as owning.
 */
trait RemovesDirectoryTree
{
    private static function removeTree(string $dir): void
    {
        if (! is_dir($dir)) {
            return;
        }

        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @psalm-suppress MixedAssignment SPL recursive iterator yields SplFileInfo. */
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPathname();
            if ($file->isLink() || $file->isFile()) {
                unlink($path);
                continue;
            }

            if ($file->isDir()) {
                rmdir($path);
            }
        }

        rmdir($dir);
    }
}
