<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\Stree;

use Koriym\SemanticLogger\Stree\NodeFormatterInterface;
use Koriym\SemanticLogger\Stree\RenderConfig;
use Koriym\SemanticLogger\Stree\TreeNode;

use function array_map;
use function explode;
use function get_object_vars;
use function http_build_query;
use function implode;
use function is_array;
use function is_object;
use function is_string;
use function str_replace;
use function strlen;
use function strrpos;
use function strtolower;
use function substr;

/**
 * Renders a `resource_request` as the request BEAR's CLI takes back:
 * `resource_request post 'app://self/users?id=koriym'`. The method stays lowercase, the only case for which
 * the CLI reads the query, and the URI is single-quoted for a POSIX shell.
 *
 * @psalm-suppress MixedAssignment Semantic Logger context values are schema-defined but mixed.
 */
final class ResourceNodeFormatter implements NodeFormatterInterface
{
    /** Encoded values longer than this are cut unless `--full`; every parameter name stays. */
    private const MAX_VALUE_LENGTH = 40;

    public function format(TreeNode $node, RenderConfig $config): string
    {
        $context = $node->context;
        $method = strtolower(self::stringValue($context, 'method'));
        $line = $method === '' ? $node->getDisplayName() : $node->getDisplayName() . ' ' . $method;

        $request = self::stringValue($context, 'uri') . self::queryString($context, $config->showFullTree);
        // POSIX single quotes by hand: escapeshellarg() changes with the platform and the locale
        $line .= " '" . str_replace("'", "'\\''", $request) . "'";

        // The recorder judged it non-replayable (a withheld credential, say), so it cannot simply run again
        return ($context['replayable'] ?? null) === false ? $line . ' [not replayable]' : $line;
    }

    /** @param array<string, mixed> $context */
    private static function stringValue(array $context, string $key): string
    {
        $value = $context[$key] ?? null;

        return is_string($value) ? $value : '';
    }

    /** @param array<string, mixed> $context */
    private static function queryString(array $context, bool $full): string
    {
        $params = $context['params'] ?? $context['query'] ?? null;
        if (is_object($params)) {
            // Frozen context values arrive as objects in the rendered log.
            $params = get_object_vars($params);
        }

        $query = is_array($params) ? http_build_query($params, '', '&') : '';
        if ($query === '' || $full) {
            return $query === '' ? '' : '?' . $query;
        }

        // http_build_query() encodes "&" and "=" inside names and values, so splitting on them is exact.
        return '?' . implode('&', array_map(self::cutValue(...), explode('&', $query)));
    }

    private static function cutValue(string $pair): string
    {
        $value = explode('=', $pair, 2)[1] ?? '';
        if (strlen($value) <= self::MAX_VALUE_LENGTH) {
            return $pair;
        }

        $kept = substr($value, 0, self::MAX_VALUE_LENGTH);
        $escape = strrpos($kept, '%');
        if ($escape !== false && $escape > self::MAX_VALUE_LENGTH - 3) {
            $kept = substr($kept, 0, $escape); // never end inside a %XX escape
        }

        $name = substr($pair, 0, strlen($pair) - strlen($value));

        return $name . $kept . '...(+' . (strlen($value) - strlen($kept)) . ')';
    }
}
