<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\TestCase;

use function array_reverse;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function file_put_contents;
use function is_array;
use function is_dir;
use function is_file;
use function is_string;
use function json_decode;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function time;
use function touch;
use function unlink;
use function uniqid;

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
    private array $cleanupDirs = [];

    protected function tearDown(): void
    {
        foreach ($this->logFiles as $logFile) {
            if (is_file($logFile)) {
                unlink($logFile);
            }
        }

        // Deepest first, so each directory is empty when rmdir() reaches it.
        foreach (array_reverse($this->cleanupDirs) as $dir) {
            if (is_dir($dir)) {
                rmdir($dir);
            }
        }
    }

    public function testResolvesAnExplicitPathAndEchoesItFirst(): void
    {
        $logFile = $this->writeLog([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                [
                    'kind' => 'open',
                    'id' => 'rr1',
                    'type' => 'resource_request',
                    'context' => ['uri' => 'app://self/products'],
                    'close' => ['type' => 'resource_response', 'context' => ['code' => 200]],
                ],
            ],
        ]);

        [$status, $output] = $this->runTree($logFile);

        $this->assertSame(0, $status, $output);
        $this->assertStringStartsWith($logFile . "\n", $output);
        $this->assertStringContainsString('resource_request', $output);
        // The close type is visible, proving the tree actually rendered rather than parsing an
        // empty/unrecognized shape (which prints only the echoed path and nothing else).
        $this->assertStringContainsString('resource_response', $output);
    }

    public function testBasenamesBodyRefWithoutTouchingTheFileOnDisk(): void
    {
        $logFile = $this->writeLog([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                [
                    'kind' => 'open',
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

        [$status, $output] = $this->runTree($logFile);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('body_ref=000001.json', $output);
        $this->assertStringNotContainsString('file:///', $output);

        $this->assertSame(
            'file:///var/log/ctx/es-bodies/20260101-000000-000000/000001.json',
            $this->readBodyRefFromDisk($logFile),
        );
    }

    public function testForwardsFlagsAfterTheLogPathToStreeVerbatim(): void
    {
        $logFile = $this->writeLog([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                ['kind' => 'open', 'id' => 's1', 'type' => 'some_type', 'context' => ['a' => 'b']],
            ],
        ]);

        // --json is a stree flag with no analogue in tree.php's own argument handling; seeing
        // it take effect proves args placed after the log path reach StreeCommand unmodified.
        [$status, $output] = $this->runTree($logFile, '--json');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('"entries"', $output);
    }

    public function testAutoDiscoversTheNewestLatestJsonWhenNoPathIsGiven(): void
    {
        $root = dirname(__DIR__, 2);
        $contextDir = $root . '/var/log/tree-render-test-' . uniqid() . '/observe';
        $this->makeDir($root . '/var');
        $this->makeDir($root . '/var/log');
        $this->makeDir(dirname($contextDir));
        $this->makeDir($contextDir);

        $older = $contextDir . '/older.json';
        $newer = $contextDir . '/newer.json';
        $this->logFiles[] = $older;
        $this->logFiles[] = $newer;

        file_put_contents($older, (string) json_encode([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [['kind' => 'open', 'id' => 'o1', 'type' => 'older_marker', 'context' => []]],
        ]));
        touch($older, time() - 60);

        file_put_contents($newer, (string) json_encode([
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [['kind' => 'open', 'id' => 'n1', 'type' => 'newer_marker', 'context' => []]],
        ]));
        touch($newer, time());

        // glob() only matches .../observe/latest.json, so this exercises the real discovery
        // glob rather than a loosened stand-in for it.
        $latest = $contextDir . '/latest.json';
        file_put_contents($latest, (string) file_get_contents($newer));
        $this->logFiles[] = $latest;

        [$status, $output] = $this->runTree(null);

        $this->assertSame(0, $status, $output);
        $this->assertStringStartsWith($latest . "\n", $output);
        $this->assertStringContainsString('newer_marker', $output);
    }

    public function testFailsCleanlyOnAMissingFile(): void
    {
        [$status, $output] = $this->runTree(sys_get_temp_dir() . '/does-not-exist-' . uniqid() . '.json');

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

    private function readBodyRefFromDisk(string $logFile): string|null
    {
        $decoded = json_decode((string) file_get_contents($logFile), true);
        if (! is_array($decoded) || ! is_array($decoded['entries'] ?? null)) {
            return null;
        }

        $entry = $decoded['entries'][0] ?? null;
        if (! is_array($entry) || ! is_array($entry['close'] ?? null)) {
            return null;
        }

        $context = $entry['close']['context'] ?? null;
        if (! is_array($context)) {
            return null;
        }

        /** @psalm-suppress MixedAssignment reading a decoded JSON tree */
        $bodyRef = $context['body_ref'] ?? null;

        return is_string($bodyRef) ? $bodyRef : null;
    }

    private function makeDir(string $dir): void
    {
        if (is_dir($dir)) {
            return;
        }

        mkdir($dir);
        $this->cleanupDirs[] = $dir;
    }

    /** @return array{0: int, 1: string} */
    private function runTree(string|null $logFile, string ...$args): array
    {
        $root = dirname(__DIR__, 2);
        $command = 'cd ' . escapeshellarg($root) . ' && ' . PHP_BINARY . ' ' . escapeshellarg(
            $root . '/skills/bear-observe/harness/tree.php',
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
