<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource\Stree;

use BEAR\EventSourcing\Resource\Stree\ResourceNodeFormatter;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeNode;
use PHPUnit\Framework\TestCase;

final class ResourceNodeFormatterTest extends TestCase
{
    private static function config(): RenderConfig
    {
        return new RenderConfig(showFullTree: false, timeThreshold: 0.0, maxLines: 0);
    }

    public function testRendersMethodUriAndParamsAsAQueryString(): void
    {
        $node = new TreeNode('resource_request_1', 'resource_request', [
            'uri' => 'app://self/users',
            'method' => 'POST',
            'params' => ['id' => 'koriym', 'name' => 'Akihito'],
            'timestamp' => '2026-06-10T12:34:56.123456+00:00',
        ]);

        $line = (new ResourceNodeFormatter())->format($node, self::config());

        $this->assertSame('request="POST app://self/users?id=koriym&name=Akihito"', $line);
    }

    public function testOmitsQueryStringWhenThereAreNoParams(): void
    {
        $node = new TreeNode('resource_request_1', 'resource_request', [
            'uri' => 'app://self/users/1',
            'method' => 'GET',
        ]);

        $line = (new ResourceNodeFormatter())->format($node, self::config());

        $this->assertSame('request="GET app://self/users/1"', $line);
    }

    public function testFallsBackToTheQueryKey(): void
    {
        $node = new TreeNode('resource_request_1', 'resource_request', [
            'uri' => 'app://self/users',
            'method' => 'POST',
            'query' => ['id' => 'koriym'],
        ]);

        $line = (new ResourceNodeFormatter())->format($node, self::config());

        $this->assertSame('request="POST app://self/users?id=koriym"', $line);
    }
}
