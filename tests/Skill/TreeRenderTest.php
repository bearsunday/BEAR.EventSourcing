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
 * tree.php is a standalone script (no autoloader, no application), so each test writes a
 * hand-built observation log and runs the real script against it.
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
            'open' => [
                [
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
        $this->assertStringContainsString('replayable=false', $output);
    }

    public function testALongValueIsTruncatedWithTheDroppedCharacterCountAndFullDisablesIt(): void
    {
        $value = str_repeat('x', 100);
        $log = [
            'open' => [
                [
                    'type' => 'becoming_open',
                    'context' => ['prop' => $value],
                ],
            ],
        ];

        [$status, $output] = $this->runTree($log);
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('prop=' . str_repeat('x', 57) . '...(+43)', $output);
        $this->assertStringNotContainsString($value, $output);

        [$status, $output] = $this->runTree($log, '--full');
        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('prop=' . $value, $output);
        $this->assertStringNotContainsString('...(+', $output);
    }

    public function testAFullyQualifiedClassNameRendersShortWhileAUriIsLeftAlone(): void
    {
        $log = [
            'open' => [
                [
                    'type' => 'becoming_open',
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

        $command = PHP_BINARY . ' ' . escapeshellarg(
            dirname(__DIR__, 2) . '/skills/bear-observe/harness/tree.php',
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
