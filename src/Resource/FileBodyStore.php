<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;
use BackedEnum;
use DateTimeInterface;
use FilesystemIterator;
use JsonException;
use JsonSerializable;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use ReflectionObject;
use SplFileInfo;
use UnexpectedValueException;
use UnitEnum;

use function array_key_exists;
use function bin2hex;
use function count;
use function explode;
use function file_exists;
use function file_put_contents;
use function get_mangled_object_vars;
use function gmdate;
use function is_array;
use function is_dir;
use function is_file;
use function is_link;
use function is_object;
use function json_encode;
use function microtime;
use function mkdir;
use function random_bytes;
use function realpath;
use function rmdir;
use function round;
use function sort;
use function spl_object_id;
use function sprintf;
use function str_starts_with;
use function strrpos;
use function strtoupper;
use function substr;
use function trigger_error;
use function trim;
use function unlink;

use const DIRECTORY_SEPARATOR;
use const E_USER_WARNING;
use const JSON_INVALID_UTF8_SUBSTITUTE;
use const JSON_PRESERVE_ZERO_FRACTION;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const JSON_UNESCAPED_UNICODE;
use const LOCK_EX;
use const SORT_STRING;

/**
 * Development-time body store: one numbered file per recorded operation.
 *
 * A file holds the response body as JSON, never rendered: an embedded request
 * is written as a `{"$request": {method, uri, params}}` marker and never run.
 * An object of the application is written with its non-public properties too;
 * an object of an installed package with its public ones only. One with no
 * property to write, or one already being written, becomes `{"$object": class}`.
 * Nesting past 64 levels becomes `{"$truncated": "depth"}`, and what follows
 * the first 50,000 values of a body `{"$truncated": "size"}`.
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
 * sessions you expect to overlap to avoid it. Paired with a log writer that
 * keeps its own count (DevQueryRepositoryLogModule's `keep`), give the store
 * one more: it prunes at the first stored body, before that request's log is
 * written and the oldest log pruned, and a generation pruned before the log
 * that references it leaves that log's `body_ref` dangling.
 *
 * Pruning is best effort. A generation that cannot be removed (permissions,
 * a held file handle, another process removing it first) raises an
 * E_USER_WARNING and is skipped; the write that triggered the prune still
 * succeeds.
 *
 * Use it for dev/debug observation (DevLogModule); production body stores
 * belong to the application.
 */
final class FileBodyStore implements BodyStoreInterface
{
    /** File that marks a directory as one this store created and may prune. */
    public const MARKER = '.bear-es-bodies';

    private const DEFAULT_KEEP = 5;
    private const MAX_DEPTH = 64;
    private const MAX_VALUES = 50_000;
    private const JSON_FLAGS = JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
        | JSON_PRESERVE_ZERO_FRACTION | JSON_INVALID_UTF8_SUBSTITUTE;

    private string|null $generationDir = null;
    private int $sequence = 0;
    private int $remaining = 0;
    /** Null treats every object as one of an installed package */
    private readonly InstalledPackageDirs|null $packageDirs;

    public function __construct(
        private readonly string $dir,
        private readonly int $keep = self::DEFAULT_KEEP,
    ) {
        self::assertSafeDirectory($dir);
        if ($this->keep < 1) {
            throw new BodyStoreException(sprintf('FileBodyStore keep must be at least 1, got %d.', $this->keep));
        }

        $this->packageDirs = InstalledPackageDirs::find();
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        // Encode before creating anything: a body that cannot be stored must not consume a $keep slot.
        try {
            $this->remaining = self::MAX_VALUES;
            $body = json_encode($this->normalize($ro->body, [], 0), self::JSON_FLAGS);
        } catch (JsonException $e) {
            throw new BodyStoreException(sprintf('Failed to encode body of %s: %s', $ro->uri, $e->getMessage()), 0, $e);
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
     * Never runs an AbstractRequest: json_encode() would, through its jsonSerialize().
     *
     * @param array<int, true> $path ids of the objects being written around $value
     *
     * @psalm-suppress MixedAssignment a body is untyped
     */
    private function normalize(mixed $value, array $path, int $depth): mixed
    {
        if ($this->remaining-- <= 0) {
            return ['$truncated' => 'size'];
        }

        if ((is_array($value) || is_object($value)) && $depth > self::MAX_DEPTH) {
            return ['$truncated' => 'depth'];
        }

        if (is_array($value)) {
            $normalized = [];
            foreach ($value as $key => $item) {
                if ($this->remaining <= 0) {
                    $normalized[$key] = ['$truncated' => 'size'];
                    break;
                }

                $normalized[$key] = $this->normalize($item, $path, $depth + 1);
            }

            return $normalized;
        }

        if (! is_object($value) || $value instanceof DateTimeInterface) {
            return $value;
        }

        if ($value instanceof AbstractRequest) {
            $uri = $value->resourceObject->uri;

            return ['$request' => [
                'method' => strtoupper($value->method->value),
                'uri' => "{$uri->scheme}://{$uri->host}{$uri->path}",
                'params' => $this->normalize($value->query, $path, $depth + 2),
            ]];
        }

        if ($value instanceof UnitEnum) {
            return $value instanceof BackedEnum ? $value->value : $value->name;
        }

        $id = spl_object_id($value);
        if (isset($path[$id])) {
            return ['$object' => $value::class];
        }

        $path[$id] = true;
        if ($value instanceof ResourceObject || $value instanceof JsonSerializable) {
            $inner = $value instanceof ResourceObject ? $value->body : $value->jsonSerialize();

            // An object handed back still counts a level, so a chain of them ends at the depth limit.
            return $this->normalize($inner, $path, is_object($inner) ? $depth + 1 : $depth);
        }

        $properties = $this->properties($value);

        return $properties === [] ? ['$object' => $value::class] : $this->normalize($properties, $path, $depth);
    }

    /**
     * Reads the state without calling the object: no getter, no __get(), no property hook, and a
     * lazy object is left uninitialized.
     *
     * @return array<array-key, mixed>
     *
     * @psalm-suppress MixedAssignment
     */
    private function properties(object $object): array
    {
        $file = (new ReflectionObject($object))->getFileName();
        $ofApplication = $this->packageDirs !== null && $file !== false && ! $this->packageDirs->contains($file);

        $properties = [];
        foreach (get_mangled_object_vars($object) as $name => $property) {
            $name = (string) $name;
            $marker = strrpos($name, "\0");
            if ($marker !== false && ! $ofApplication) {
                continue;
            }

            $plain = $marker === false ? $name : substr($name, $marker + 1);
            if (! array_key_exists($plain, $properties)) {
                $properties[$plain] = $property;
            }
        }

        return $properties;
    }

    /**
     * Create this instance's generation directory under the shared root, then
     * prune sibling generations down to $keep. Every call names a directory
     * that cannot already exist, so mkdir() either creates it or fails outright.
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
        self::pruneGenerations($rootDir, $keep, $generationDir);

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

    /**
     * Remove owned generations beyond $keep, oldest first; foreign entries are never touched or counted.
     * The generation just created always survives, even when a clock running behind sorts it first.
     */
    private static function pruneGenerations(string $rootDir, int $keep, string $current): void
    {
        $owned = [];
        foreach (new FilesystemIterator($rootDir) as $entry) {
            /** @var SplFileInfo $entry */
            if (
                $entry->isLink()
                || ! $entry->isDir()
                || $entry->getPathname() === $current
                || ! is_file($entry->getPathname() . DIRECTORY_SEPARATOR . self::MARKER)
            ) {
                continue;
            }

            $owned[] = $entry->getFilename();
        }

        sort($owned, SORT_STRING);
        $overflow = count($owned) - ($keep - 1);
        for ($i = 0; $i < $overflow; $i++) {
            $generation = $rootDir . DIRECTORY_SEPARATOR . $owned[$i];
            try {
                self::removeGeneration($generation);
            } catch (BodyStoreException | UnexpectedValueException $e) {
                trigger_error(
                    sprintf('FileBodyStore could not prune %s: %s', $generation, $e->getMessage()),
                    E_USER_WARNING,
                );
            }
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
                continue; // removed last: a prune that fails partway leaves the generation marked for the next one
            }

            if ($file->isLink() || $file->isFile()) {
                if (! unlink($path) && file_exists($path)) {
                    throw new BodyStoreException(sprintf('Failed to remove body store file: %s', $path));
                }

                continue;
            }

            if ($file->isDir() && ! rmdir($path) && is_dir($path)) {
                throw new BodyStoreException(sprintf('Failed to remove body store directory: %s', $path));
            }
        }
    }
}
