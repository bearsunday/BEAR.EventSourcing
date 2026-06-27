<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use PHPUnit\Framework\TestCase;

use function BEAR\EventSourcing\Examples\exampleSemanticLog;
use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function substr_count;

use const JSON_PRETTY_PRINT;
use const JSON_THROW_ON_ERROR;
use const JSON_UNESCAPED_SLASHES;
use const PHP_BINARY;

final class ExamplesTest extends TestCase
{
    public static function setUpBeforeClass(): void
    {
        require_once dirname(__DIR__) . '/examples/bootstrap.php';
    }

    public function testSemanticLogFixtureMatchesGeneratedLog(): void
    {
        $generated = json_encode(
            exampleSemanticLog()->toArray(),
            JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR,
        );
        $fixture = file_get_contents(dirname(__DIR__) . '/examples/semantic-log.json');
        $this->assertNotFalse($fixture);

        $this->assertSame(
            json_decode($fixture, true, 512, JSON_THROW_ON_ERROR),
            json_decode($generated, true, 512, JSON_THROW_ON_ERROR),
            'examples/semantic-log.json is out of sync with exampleSemanticLog(). Regenerate it.',
        );
    }

    public function testExtractExampleShowsDefaultExtractionBoundary(): void
    {
        $output = self::runExample('extract.php');

        $this->assertSame(3, substr_count($output, '"method":'));
        $this->assertStringContainsString('"method": "POST"', $output);
        $this->assertStringContainsString('"method": "PUT"', $output);
        $this->assertStringNotContainsString('"method": "GET"', $output);
        $this->assertStringContainsString('"uri": "app://self/inventory/SKU-1"', $output);
        // result is taken from close.context.body, not from a body_ref; "status": "accepted"
        // only appears in the orders response body, so it proves the body became the event result.
        $this->assertStringContainsString('"status": "accepted"', $output);
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
