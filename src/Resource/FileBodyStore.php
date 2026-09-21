<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use FilesystemIterator;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use SplFileInfo;

use function bin2hex;
use function count;
use function explode;
use function file_put_contents;
use function gmdate;
use function is_dir;
use function is_file;
use function is_link;
use function microtime;
use function mkdir;
use function random_bytes;
use function realpath;
use function rmdir;
use function round;
use function sort;
use function sprintf;
use function str_starts_with;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const LOCK_EX;
use const SORT_STRING;

/**
 * Development-time body store: one numbered file per recorded operation.
 *
 * Construction writes nothing to disk — the first stored body creates a
 * fresh, uniquely named generation directory under `$dir` and marks it
 * owned; every later call in the same instance's lifetime reuses that
 * generation. Because a generation is never named twice, one root directory
 * can be shared across sessions/processes without their sequences
 * colliding. After creating a generation, the store prunes sibling
 * generations (directories it marked itself) down to `$keep`, oldest
 * first; anything under `$dir` without the marker is left alone and never
 * counted toward that cap.
 *
 * Retention counts marked generations, not active sessions: if more than
 * `$keep` other sessions create generations under the same root while an
 * earlier session is still writing, that session's generation can be
 * pruned out from under it — its already-written `body_ref`s stop
 * resolving and its next write throws. `SemanticLogInvoker` catches a
 * `BodyStoreInterface` failure and records it in the close context rather
 * than propagating it, so this degrades one entry to a missing `body_ref`
 * rather than breaking the request; size `$keep` above the number of
 * sessions you expect to overlap to avoid it. A generation that cannot be
 * deleted (permissions, a held file handle) also fails the write that
 * triggered the prune, since pruning runs before the new generation's
 * first file is written.
 *
 * Use it for dev/debug observation (DevLogModule); production body stores
 * belong to the application.
 */
final class FileBodyStore implements BodyStoreInterface
{
    /** File that marks a directory as one this store created and may clear. */
    public const string MARKER = '.bear-es-bodies';

    private const int DEFAULT_KEEP = 5;

    private string|null $generationDir = null;
    private int $sequence = 0;

    public function __construct(
        private readonly string $dir,
        private readonly int $keep = self::DEFAULT_KEEP,
    ) {
        self::assertSafeDirectory($dir);
        if ($this->keep < 1) {
            throw new BodyStoreException(sprintf('FileBodyStore keep must be at least 1, got %d.', $this->keep));
        }
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        // Render before creating anything: a failed render must leave no trace —
        // no generation directory consuming a $keep slot for a body never stored.
        // toString(), not (string) $ro: the latter swallows a render failure and
        // returns '', which would write an empty file behind a valid body_ref.
        // Restore the view afterwards so observing the body does not freeze the
        // response representation for later stages of the request.
        $priorView = $ro->view;
        try {
            $body = $ro->toString();
        } finally {
            $ro->view = $priorView;
        }

        $generationDir = $this->generationDir ??= self::createGeneration($this->dir, $this->keep);
        $file = $generationDir . DIRECTORY_SEPARATOR . sprintf('%06d.json', ++$this->sequence);

        $bytes = file_put_contents($file, $body, LOCK_EX);
        if ($bytes === false) {
            throw new BodyStoreException(sprintf('Failed to write body file: %s', $file));
        }

        return 'file://' . $file;
    }

    /**
     * Empty and unmark a directory this store (or a prior run of it) owns.
     *
     * A directory qualifies by carrying the marker; an empty, unmarked
     * directory is adopted (marked, then immediately cleared) so a first call
     * against a fresh path succeeds. Anything else — populated and unmarked —
     * is refused via {@see UnownedDirectoryException}, a distinguishable
     * subtype of the general failures below (I/O errors, unsafe paths) raised
     * here as {@see BodyStoreException}.
     */
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

    /**
     * Create this instance's generation directory under the shared root, then
     * prune sibling generations down to $keep. Every call names a directory
     * that cannot already exist, so — unlike {@see clearDirectory()} — there
     * is nothing to adopt here: mkdir() either creates it or fails outright.
     */
    private static function createGeneration(string $rootDir, int $keep): string
    {
        self::assertSafeDirectory($rootDir);

        if (is_file($rootDir) || is_link($rootDir)) {
            throw new BodyStoreException(sprintf('Body store path is not a directory: %s', $rootDir));
        }

        if (! is_dir($rootDir) && ! mkdir($rootDir, 0775, true) && ! is_dir($rootDir)) {
            throw new BodyStoreException(sprintf('Failed to create body store directory: %s', $rootDir));
        }

        $generationDir = $rootDir . DIRECTORY_SEPARATOR . self::generationName();
        if (! mkdir($generationDir, 0775)) {
            throw new BodyStoreException(
                sprintf('Failed to create body store generation directory: %s', $generationDir),
            );
        }

        self::markOwned($generationDir);
        self::pruneGenerations($rootDir, $keep);

        return $generationDir;
    }

    /**
     * A fixed-width timestamp plus a random suffix: directory names sort
     * chronologically (oldest-first pruning is a plain string sort), and the
     * suffix keeps two generations started within the same microsecond from
     * colliding.
     */
    private static function generationName(): string
    {
        $now = microtime(true);
        $seconds = (int) $now;
        $micros = (int) round(($now - (float) $seconds) * 1_000_000.0);
        if ($micros >= 1_000_000) {
            $seconds++;
            $micros -= 1_000_000;
        }

        return gmdate('Ymd-His', $seconds) . '-' . sprintf('%06d', $micros) . '-' . bin2hex(random_bytes(4));
    }

    /** Remove owned generations beyond $keep, oldest first; foreign entries are never touched or counted. */
    private static function pruneGenerations(string $rootDir, int $keep): void
    {
        $owned = [];
        foreach (new FilesystemIterator($rootDir) as $entry) {
            /** @var SplFileInfo $entry */
            if (! $entry->isDir() || ! is_file($entry->getPathname() . DIRECTORY_SEPARATOR . self::MARKER)) {
                continue;
            }

            $owned[] = $entry->getFilename();
        }

        sort($owned, SORT_STRING);
        $overflow = count($owned) - $keep;
        for ($i = 0; $i < $overflow; $i++) {
            self::removeGeneration($rootDir . DIRECTORY_SEPARATOR . $owned[$i]);
        }
    }

    private static function removeGeneration(string $dir): void
    {
        self::clearContents($dir);
        self::removeMarker($dir);
        if (! rmdir($dir)) {
            // Left unmarked: an empty husk here is inert clutter, invisible to a later
            // prune pass. Re-marking it would make a *persistent* rmdir failure (a
            // stuck permission, a held handle) retried by every future session's
            // createGeneration() — turning one bad directory into a permanent block
            // on all observation, which is worse than the leak.
            throw new BodyStoreException(sprintf('Failed to remove body store generation directory: %s', $dir));
        }
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

        throw new UnownedDirectoryException(
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
