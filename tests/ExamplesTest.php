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

        $this->assertSame(2, substr_count($output, '"method":'));
        $this->assertStringContainsString('"method": "POST"', $output);
        $this->assertStringNotContainsString('"method": "GET"', $output);
        // The inventory PUT nested inside POST orders is observation, not an event.
        $this->assertStringNotContainsString('"method": "PUT"', $output);
        $this->assertStringNotContainsString('app://self/inventory/SKU-1', $output);
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

        $this->assertStringContainsString('"stored": 2', $output);
        $this->assertSame(2, substr_count($output, '"method":'));
    }

    public function testTreeExampleRendersCleanResourceTree(): void
    {
        $output = self::runExample('tree.php');

        // ResourceNodeFormatter renders the open line as one resource operation;
        // the close line keeps the body_ref pointer for drilling into detail.
        // No timestamp noise.
        $this->assertStringContainsString('request="PUT app://self/inventory/SKU-1?sku=SKU-1&quantity=1"', $output);
        $this->assertStringContainsString('└── code=201 body_ref=file://var/es/bodies/000002.json', $output);
        $this->assertStringNotContainsString('timestamp=', $output);

        // An embedded non-resource operation (a media query) is a leaf event:
        // intent and wall time inline, nested under the resource that ran it.
        $this->assertStringContainsString('media_query name=inventory_reserve durationMs=0.42 [event]', $output);
    }

    public function testObserveExampleRunsTheLiveObservationPipeline(): void
    {
        $output = self::runExample('observe/observe.php');

        // Live wiring, not a fixture: the internal inventory PUT appears as a
        // child node of the POST orders request.
        $this->assertStringContainsString('request="POST app://self/orders?order_id=O-1000"', $output);
        $this->assertStringContainsString('├── request="PUT app://self/inventory?sku=SKU-1&quantity=1"', $output);
        $this->assertStringContainsString('code=409', $output);
        $this->assertStringContainsString('Bodies behind body_ref (4 file(s))', $output);

        // Extraction boundary: "params=" is printed once per extracted event,
        // so a count of 1 proves only the root POST became an event.
        $this->assertSame(1, substr_count($output, 'params='));
        $this->assertStringContainsString('POST app://self/orders  params=', $output);
        $this->assertStringContainsString('re-extraction produced identical ids: yes', $output);
        $this->assertStringContainsString('inventory events: 0', $output);

        $this->assertStringContainsString('events stored after re-append: 1 (no duplicates)', $output);
        $this->assertStringContainsString('stored rows after re-append: 1', $output);
        $this->assertSame(1, substr_count($output, 'replay '));

        // Exit 0 (asserted by runExample) only proves [10] did not fail; this
        // proves the schema validation actually ran to its success line.
        $this->assertStringContainsString('All contexts validate successfully!', $output);
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
