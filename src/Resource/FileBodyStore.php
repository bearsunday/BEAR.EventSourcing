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

final class FileBodyStore implements BodyStoreInterface
{
    private const MARKER = '.bear-es-bodies';

    private int $sequence = 0;

    public function __construct(
        private readonly string $dir,
    ) {
        self::ensureDirectory($dir);
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        $file = $this->dir . DIRECTORY_SEPARATOR . sprintf('%06d.json', ++$this->sequence);

        // Render through toString(), not (string) $ro: the latter swallows a render
        // failure and returns '', which would write an empty file behind a valid
        // body_ref. Restore the view afterwards so observing the body does not freeze
        // the response representation for later stages of the request.
        $priorView = $ro->view;
        try {
            $body = $ro->toString();
        } finally {
            $ro->view = $priorView;
        }

        $bytes = file_put_contents($file, $body, LOCK_EX);
        if ($bytes === false) {
            throw new BodyStoreException(sprintf('Failed to write body file: %s', $file));
        }

        return 'file://' . $file;
    }

    public static function clearDirectory(string $dir): void
    {
        self::ensureDirectory($dir);
        self::assertOwned($dir);
        self::clearContents($dir);
        // Remove the ownership marker only after everything else is gone, so a
        // cleanup that fails partway leaves the directory still marked as owned
        // and clearable on the next attempt.
        self::removeMarker($dir);
    }

    private static function ensureDirectory(string $dir): void
    {
        self::assertSafeDirectory($dir);
        if (is_dir($dir)) {
            // Adopt a pre-existing directory only while it is still empty, so a
            // misconfigured bodyDir pointing at populated data is never cleared.
            if (self::isEmpty($dir)) {
                self::markOwned($dir);
            }

            return;
        }

        if (is_file($dir) || is_link($dir)) {
            throw new BodyStoreException(sprintf('Body store path is not a directory: %s', $dir));
        }

        if (! mkdir($dir, 0775, true) && ! is_dir($dir)) {
            throw new BodyStoreException(sprintf('Failed to create body store directory: %s', $dir));
        }

        self::markOwned($dir);
    }

    /**
     * Refuse to clear a directory this store does not own. A directory is owned
     * once it carries the marker, which is written only while adopting an empty
     * directory or creating a new one — never over pre-existing contents. This
     * stops `new DevLogModule('/path/to/project')` from wiping the project.
     */
    private static function assertOwned(string $dir): void
    {
        if (is_file($dir . DIRECTORY_SEPARATOR . self::MARKER)) {
            return;
        }

        throw new BodyStoreException(
            sprintf('Refusing to clear a body store directory without an ownership marker: %s', $dir),
        );
    }

    private static function isEmpty(string $dir): bool
    {
        return ! (new FilesystemIterator($dir))->valid();
    }

    private static function markOwned(string $dir): void
    {
        $marker = $dir . DIRECTORY_SEPARATOR . self::MARKER;
        if (! is_file($marker) && file_put_contents($marker, '') === false) {
            throw new BodyStoreException(sprintf('Failed to mark body store directory: %s', $marker));
        }
    }

    private static function removeMarker(string $dir): void
    {
        $marker = $dir . DIRECTORY_SEPARATOR . self::MARKER;
        if (is_file($marker) && ! unlink($marker)) {
            throw new BodyStoreException(sprintf('Failed to remove body store marker: %s', $marker));
        }
    }

    private static function assertSafeDirectory(string $dir): void
    {
        $trimmed = trim($dir);
        if ($trimmed === '' || $trimmed === DIRECTORY_SEPARATOR) {
            throw new BodyStoreException('Unsafe body store directory.');
        }

        if (! str_starts_with($dir, DIRECTORY_SEPARATOR)) {
            throw new BodyStoreException(sprintf('Body store directory must be absolute: %s', $dir));
        }

        foreach (explode(DIRECTORY_SEPARATOR, $dir) as $segment) {
            if ($segment === '.' || $segment === '..') {
                throw new BodyStoreException(sprintf('Body store directory must not contain dot segments: %s', $dir));
            }
        }

        if (is_link($dir)) {
            throw new BodyStoreException(sprintf('Body store directory must not be a symlink: %s', $dir));
        }

        if (realpath($dir) === DIRECTORY_SEPARATOR) {
            throw new BodyStoreException('Unsafe body store directory.');
        }
    }

    private static function clearContents(string $dir): void
    {
        $marker = $dir . DIRECTORY_SEPARATOR . self::MARKER;
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS),
            RecursiveIteratorIterator::CHILD_FIRST,
        );

        /** @psalm-suppress MixedAssignment SPL recursive iterator yields SplFileInfo. */
        foreach ($iterator as $file) {
            /** @var SplFileInfo $file */
            $path = $file->getPathname();
            if ($path === $marker) {
                continue; // preserved until clearDirectory removes it last
            }

            if ($file->isLink() || $file->isFile()) {
                if (! unlink($path)) {
                    throw new BodyStoreException(sprintf('Failed to remove body store file: %s', $path));
                }

                continue;
            }

            if ($file->isDir() && ! rmdir($path)) {
                throw new BodyStoreException(sprintf('Failed to remove body store directory: %s', $path));
            }
        }
    }
}
