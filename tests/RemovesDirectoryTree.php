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
 * Removes a test's body-store root and everything under it; the store itself only prunes the generations it marked.
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
