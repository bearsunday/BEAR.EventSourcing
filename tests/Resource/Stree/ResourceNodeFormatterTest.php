<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource\Stree;

use BEAR\EventSourcing\Resource\Stree\ResourceNodeFormatter;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeNode;
use PHPUnit\Framework\TestCase;

use function dirname;
use function file_get_contents;
use function json_decode;
use function str_repeat;

use const JSON_THROW_ON_ERROR;

final class ResourceNodeFormatterTest extends TestCase
{
    /** @param array<string, mixed> $context */
    private static function format(array $context, bool $full = false): string
    {
        $node = new TreeNode('resource_request_1', 'resource_request', $context);
        $config = new RenderConfig(showFullTree: $full, timeThreshold: 0.0, maxLines: 0);

        return (new ResourceNodeFormatter())->format($node, $config);
    }

    public function testRendersTheRequestBearsCliTakesBack(): void
    {
        $line = self::format([
            'uri' => 'app://self/users',
            'method' => 'POST',
            'params' => ['id' => 'koriym', 'name' => 'Akihito'],
            'timestamp' => '2026-06-10T12:34:56.123456+00:00',
        ]);

        $this->assertSame("resource_request post 'app://self/users?id=koriym&name=Akihito'", $line);
    }

    public function testOmitsQueryStringWhenThereAreNoParams(): void
    {
        $line = self::format(['uri' => 'app://self/users/1', 'method' => 'GET']);

        $this->assertSame("resource_request get 'app://self/users/1'", $line);
    }

    public function testCutsEachLongValueAndKeepsEveryName(): void
    {
        $line = self::format([
            'uri' => 'app://self/notes',
            'method' => 'GET',
            'params' => ['note' => str_repeat('a', 45), 'page' => 2],
        ]);

        $expected = "resource_request get 'app://self/notes?note=" . str_repeat('a', 40) . "...(+5)&page=2'";
        $this->assertSame($expected, $line);
    }

    public function testFullKeepsEveryValueWhole(): void
    {
        $line = self::format([
            'uri' => 'app://self/notes',
            'method' => 'GET',
            'params' => ['note' => str_repeat('a', 45), 'page' => 2],
        ], full: true);

        $this->assertSame("resource_request get 'app://self/notes?note=" . str_repeat('a', 45) . "&page=2'", $line);
    }

    public function testFallsBackToTheQueryKey(): void
    {
        $line = self::format(['uri' => 'app://self/users', 'method' => 'POST', 'query' => ['id' => 'koriym']]);

        $this->assertSame("resource_request post 'app://self/users?id=koriym'", $line);
    }

    public function testComposerJsonDeclaresThisFormatterForStree(): void
    {
        $composerJson = (string) file_get_contents(dirname(__DIR__, 3) . '/composer.json');
        /** @var array{extra: array{stree: array{formatters: array<string, string>}}} $composer */
        $composer = json_decode($composerJson, true, 512, JSON_THROW_ON_ERROR);

        $this->assertSame(ResourceNodeFormatter::class, $composer['extra']['stree']['formatters']['resource_request']);
    }
}
