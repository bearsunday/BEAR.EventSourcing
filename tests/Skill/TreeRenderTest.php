<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_put_contents;
use function is_file;
use function json_encode;
use function str_repeat;
use function sys_get_temp_dir;
use function unlink;
use function uniqid;

use const PHP_BINARY;

/**
 * tree.php delegates rendering to stree in-process (via vendor/autoload.php), so each test
 * writes a hand-built observation log in the current entries/kind shape and runs the real
 * script against it with this repo's own root as cwd — the one place vendor/autoload.php is
 * guaranteed to resolve.
 */
final class TreeRenderTest extends TestCase
{
    /** @var list<string> */
    private array $logFiles = [];

    protected function tearDown(): void
    {
        foreach ($this->logFiles as $logFile) {
            if (is_file($logFile)) {
                unlink($logFile);
            }
        }
    }

    public function testBooleanContextValuesRenderAsTrueOrFalseNotEmpty(): void
    {
        $log = [
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                [
                    'kind' => 'open',
                    'id' => 'rr1',
                    'type' => 'resource_request',
                    'context' => ['replayable' => true],
                    'close' => [
                        'type' => 'resource_response',
                        'context' => ['replayable' => false],
                    ],
                ],
            ],
        ];

        [$status, $output] = $this->runTree($log);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('replayable=true', $output);
        // The close line carries the diff (open->close) on a changed key, not a standalone
        // "replayable=false" — the close context differs from the open context on this key.
        $this->assertStringContainsString('replayable=true→false', $output);
    }

    public function testALongValueIsTruncatedAndFullAddsTheCompleteLeaf(): void
    {
        $value = str_repeat('x', 100);
        $log = [
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                [
                    'kind' => 'open',
                    'id' => 's1',
                    'type' => 'some_type',
                    'context' => ['prop' => $value],
                ],
            ],
        ];

        // The exact cut format (drop-count marker, truncation threshold) is
        // koriym/semantic-logger's own contract (SignalExtractorTest); this only asserts tree.php
        // delegates truncation to stree instead of showing the raw value by default, and that
        // --full adds the complete value as its own leaf line (the compact signal line itself
        // still truncates in --full mode — only context-array keys expand inline there).
        [$status, $output] = $this->runTree($log);
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('prop=' . str_repeat('x', 10), $output);
        $this->assertStringNotContainsString($value, $output);

        [$status, $output] = $this->runTree($log, '--full');
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('prop: ' . $value, $output);
    }

    public function testAFullyQualifiedClassNameRendersShortWhileAUriIsLeftAlone(): void
    {
        // A generic type, not becoming_open: that type has its own dedicated display formatter
        // ("becoming <ClassName>", dropping every other key) unrelated to the generic
        // FQCN-shortening this test exercises.
        $log = [
            '$schema' => 'https://koriym.github.io/Koriym.SemanticLogger/schemas/semantic-log.json',
            'entries' => [
                [
                    'kind' => 'open',
                    'id' => 's2',
                    'type' => 'some_type',
                    'context' => [
                        'input' => 'MyVendor\\BeMart\\Be\\Input\\AdminLoginInput',
                        'uri' => 'app://self/products',
                    ],
                ],
            ],
        ];

        [$status, $output] = $this->runTree($log);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('input=AdminLoginInput', $output);
        $this->assertStringNotContainsString('MyVendor\\BeMart', $output);
        $this->assertStringContainsString('uri=app://self/products', $output);
    }

    /**
     * @param array<string, mixed> $log
     *
     * @return array{0: int, 1: string}
     */
    private function runTree(array $log, string ...$args): array
    {
        $logFile = sys_get_temp_dir() . '/tree-render-' . uniqid() . '.json';
        $this->logFiles[] = $logFile;
        file_put_contents($logFile, (string) json_encode($log));

        $root = dirname(__DIR__, 2);
        $command = 'cd ' . escapeshellarg($root) . ' && ' . PHP_BINARY . ' ' . escapeshellarg(
            $root . '/skills/bear-observe/harness/tree.php',
        ) . ' ' . escapeshellarg($logFile);
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
