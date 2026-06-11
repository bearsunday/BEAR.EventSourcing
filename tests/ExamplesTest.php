<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function substr_count;

use const PHP_BINARY;

final class ExamplesTest extends TestCase
{
    public function testExtractExampleShowsDefaultExtractionBoundary(): void
    {
        $output = self::runExample('extract.php');

        $this->assertSame(3, substr_count($output, '"method":'));
        $this->assertStringContainsString('"method": "POST"', $output);
        $this->assertStringContainsString('"method": "PUT"', $output);
        $this->assertStringNotContainsString('"method": "GET"', $output);
        $this->assertStringContainsString('"uri": "app://self/inventory/SKU-1"', $output);
    }

    public function testReplayExampleCanIncludeDevelopmentReads(): void
    {
        $output = self::runExample('replay.php');

        $this->assertSame(2, substr_count($output, '"method":'));
        $this->assertStringContainsString('"method": "POST"', $output);
        $this->assertStringContainsString('"method": "GET"', $output);
        $this->assertStringContainsString('"uri": "app://self/users/koriym"', $output);
    }

    public function testStoreExamplePersistsExtractedEvents(): void
    {
        $output = self::runExample('store.php');

        $this->assertStringContainsString('"stored": 3', $output);
        $this->assertSame(3, substr_count($output, '"method":'));
    }

    private static function runExample(string $script): string
    {
        $output = [];
        $status = 0;
        exec(PHP_BINARY . ' ' . escapeshellarg(dirname(__DIR__) . '/examples/' . $script), $output, $status);
        self::assertSame(0, $status);

        $text = '';
        foreach ($output as $line) {
            self::assertIsString($line);
            $text .= $line . "\n";
        }

        return $text;
    }
}
