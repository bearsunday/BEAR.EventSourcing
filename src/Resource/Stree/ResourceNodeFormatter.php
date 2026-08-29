<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\Stree;

use Koriym\SemanticLogger\Stree\NodeFormatterInterface;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeNode;

use function get_object_vars;
use function http_build_query;
use function is_array;
use function is_object;
use function is_string;
use function sprintf;

/**
 * Render a `resource_request` node as a single readable resource operation:
 *
 *     request="POST app://self/users?id=koriym&name=Akihito"
 *
 * The intent (method + uri + params as a query string) is one line; stree
 * renders the close (`code` + `body`/`body_ref`) beneath it. Register this for
 * the `resource_request` type via a {@see \Koriym\SemanticLogger\Stree\FormatterRegistry}.
 *
 * @psalm-suppress MixedAssignment Semantic Logger context values are schema-defined but mixed.
 */
final class ResourceNodeFormatter implements NodeFormatterInterface
{
    public function format(TreeNode $node, RenderConfig $config): string
    {
        $context = $node->context;
        $method = self::stringValue($context, 'method');
        $uri = self::stringValue($context, 'uri');

        $request = $method === '' ? $uri : $method . ' ' . $uri;

        return sprintf('request="%s"', $request . self::queryString($context));
    }

    /** @param array<string, mixed> $context */
    private static function stringValue(array $context, string $key): string
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /** @param array<string, mixed> $context */
    private static function queryString(array $context): string
    {
        $params = $context['params'] ?? $context['query'] ?? null;
        if (is_object($params)) {
            // Frozen context values arrive as objects in the rendered log.
            $params = get_object_vars($params);
        }

        if (! is_array($params) || $params === []) {
            return '';
        }

        return '?' . http_build_query($params);
    }
}
