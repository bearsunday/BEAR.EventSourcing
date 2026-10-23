<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\TestCase;

use function basename;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function file_put_contents;
use function glob;
use function is_dir;
use function is_file;
use function is_link;
use function json_encode;
use function mkdir;
use function realpath;
use function rmdir;
use function symlink;
use function sys_get_temp_dir;
use function time;
use function touch;
use function unlink;
use function uniqid;

use const GLOB_BRACE;
use const PHP_BINARY;

/**
 * tree.php is a thin wrapper around stree: it resolves which log to read (an explicit path, or
 * the newest var/log/*\/observe/latest.json), echoes the resolved path, rewrites `body_ref` to
 * its basename, and forwards everything else verbatim to StreeCommand in-process. These tests
 * cover exactly that wrapper-owned surface — what each context value or signal line looks like
 * once stree renders it is koriym/semantic-logger's own contract (SignalExtractorTest); a test
 * here that pinned truncation length, FQCN-shortening, or close-diff arrow formatting would just
 * re-pin that contract from downstream and silently drift whenever stree's own rendering changes.
 */
final class TreeRenderTest extends TestCase
{
    /** @var list<string> */
    private array $logFiles = [];

    /** @var list<string> */
    private array $tmpDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->logFiles as $logFile) {
            if (is_file($logFile)) {
                unlink($logFile);
            }
        }

        foreach ($this->tmpDirs as $dir) {
            $this->removeRecursively($dir);
        }
    }

    public function testResolvesAnExplicitPathAndEchoesItFirst(): void
    {
        $logFile = $this->writeLog([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'log' => [
                [
                    'id' => 'rr1',
                    'type' => 'resource_request',
                    'context' => ['uri' => 'app://self/products'],
                    'close' => ['type' => 'resource_response', 'context' => ['code' => 200]],
                ],
            ],
        ]);

        [$status, $output] = $this->runTree(dirname(__DIR__, 2), $logFile);

        $this->assertSame(0, $status, $output);
        $this->assertStringStartsWith($logFile . "\n", $output);
        $this->assertStringContainsString('resource_request', $output);
        // The close type is visible, proving the tree actually rendered rather than parsing an
        // empty/unrecognized shape (which prints only the echoed path and nothing else).
        $this->assertStringContainsString('resource_response', $output);
    }

    public function testBasenamesBodyRefWithoutTouchingTheFileOnDisk(): void
    {
        $written = (string) json_encode([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'log' => [
                [
                    'id' => 'rr1',
                    'type' => 'resource_request',
                    'context' => ['uri' => 'app://self/products'],
                    'close' => [
                        'type' => 'resource_response',
                        'context' => [
                            'code' => 200,
                            'body_ref' => 'file:///var/log/ctx/es-bodies/20260101-000000-000000/000001.json',
                        ],
                    ],
                ],
            ],
        ]);
        $logFile = sys_get_temp_dir() . '/tree-render-' . uniqid() . '.json';
        $this->logFiles[] = $logFile;
        file_put_contents($logFile, $written);

        [$status, $output] = $this->runTree(dirname(__DIR__, 2), $logFile);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('body_ref=000001.json', $output);
        $this->assertStringNotContainsString('file:///', $output);

        // Byte-for-byte: the source file was never touched, not merely "still has this one field".
        $this->assertSame($written, file_get_contents($logFile));
    }

    public function testForwardsFlagsAfterTheLogPathToStreeVerbatim(): void
    {
        $logFile = $this->writeLog([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'log' => [
                ['id' => 's1', 'type' => 'some_type', 'context' => ['a' => 'b']],
            ],
        ]);

        // --json is a stree flag with no analogue in tree.php's own argument handling; seeing
        // it take effect proves args placed after the log path reach StreeCommand unmodified.
        [$status, $output] = $this->runTree(dirname(__DIR__, 2), $logFile, '--json');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('"log"', $output);
    }

    public function testAutoDiscoversTheNewestLatestJsonWhenNoPathIsGiven(): void
    {
        // Runs with an isolated cwd (a temp dir with vendor/ symlinked in, so
        // vendor/autoload.php still resolves) rather than this repo's own working tree: the
        // discovery glob is var/log/*/observe/latest.json relative to cwd, and this repo has
        // no var/ of its own to seed safely.
        $cwd = sys_get_temp_dir() . '/tree-render-cwd-' . uniqid();
        $this->tmpDirs[] = $cwd;
        mkdir($cwd);
        symlink(dirname(__DIR__, 2) . '/vendor', $cwd . '/vendor');

        $contextDir = $cwd . '/var/log/ctx/observe';
        mkdir($contextDir, 0777, true);

        $older = $contextDir . '/older.json';
        file_put_contents($older, (string) json_encode([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'log' => [['id' => 'o1', 'type' => 'older_marker', 'context' => []]],
        ]));
        touch($older, time() - 60);

        $newer = $contextDir . '/newer.json';
        file_put_contents($newer, (string) json_encode([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'log' => [['id' => 'n1', 'type' => 'newer_marker', 'context' => []]],
        ]));
        touch($newer, time());

        // glob() only matches .../observe/latest.json, so this exercises the real discovery
        // glob rather than a loosened stand-in for it.
        $latest = $contextDir . '/latest.json';
        file_put_contents($latest, (string) file_get_contents($newer));

        [$status, $output] = $this->runTree($cwd, null);

        $this->assertSame(0, $status, $output);
        $resolvedLatest = realpath($latest);
        $this->assertIsString($resolvedLatest);
        $this->assertStringStartsWith($resolvedLatest . "\n", $output);
        $this->assertStringContainsString('newer_marker', $output);
    }

    public function testFailsCleanlyOnAMissingFile(): void
    {
        [$status, $output] = $this->runTree(
            dirname(__DIR__, 2),
            sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.json',
        );

        $this->assertSame(1, $status, $output);
        $this->assertStringContainsString('FAIL', $output);
        $this->assertStringContainsString('no such file', $output);
    }

    /** @param array<string, mixed> $log */
    private function writeLog(array $log): string
    {
        $logFile = sys_get_temp_dir() . '/tree-render-' . uniqid() . '.json';
        $this->logFiles[] = $logFile;
        file_put_contents($logFile, (string) json_encode($log));

        return $logFile;
    }

    private function removeRecursively(string $dir): void
    {
        if (is_link($dir)) {
            unlink($dir);

            return;
        }

        if (is_file($dir)) {
            unlink($dir);

            return;
        }

        if (! is_dir($dir)) {
            return;
        }

        $entries = glob($dir . '/{,.}*', GLOB_BRACE);
        foreach ($entries === false ? [] : $entries as $entry) {
            $base = basename($entry);
            if ($base === '.' || $base === '..') {
                continue;
            }

            $this->removeRecursively($entry);
        }

        rmdir($dir);
    }

    /** @return array{0: int, 1: string} */
    private function runTree(string $cwd, string|null $logFile, string ...$args): array
    {
        $command = 'cd ' . escapeshellarg($cwd) . ' && ' . PHP_BINARY . ' ' . escapeshellarg(
            dirname(__DIR__, 2) . '/skills/bear-observe/harness/tree.php',
        );
        if ($logFile !== null) {
            $command .= ' ' . escapeshellarg($logFile);
        }

        foreach ($args as $arg) {
            $command .= ' ' . escapeshellarg($arg);
        }

        $output = [];
        $status = 0;
        exec($command . ' 2>&1', $output, $status);

        $text = '';
        foreach ($output as $line) {
            self::assertIsString($line);
            $text .= $line . "\n";
        }

        return [$status, $text];
    }
}
