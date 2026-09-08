#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Renders an observation log as a tree, naming the type that closes each scope.
 *
 * Usage: php tree.php [log.json]   (default: the newest latest.json under var/log)
 *
 * `stree` prints the closing context but not its type, and here the type carries the answer:
 * `get` closed by `cache_hit` and `get` closed by `cache_miss` have the same context keys.
 */

$argvList = $argv;
array_shift($argvList);

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

$file = $argvList[0] ?? null;
if ($file === null) {
    $found = glob(getcwd() . '/var/log/*/observe/latest.json') ?: [];
    $found !== [] || fail('no var/log/*/observe/latest.json — run a request first, or pass a path');
    usort($found, static fn (string $a, string $b): int => filemtime($b) <=> filemtime($a));
    $file = $found[0];
}

is_file($file) || fail("no such file: {$file}");
$log = json_decode((string) file_get_contents($file), true);
is_array($log) || fail("not JSON: {$file}");

/** @param array<string, mixed> $context */
function summarize(array $context): string
{
    $parts = [];
    foreach ($context as $key => $value) {
        if ($key === 'timestamp' || $value === null || $value === []) {
            continue;
        }

        $text = is_scalar($value) ? (string) $value : (string) json_encode($value, JSON_UNESCAPED_SLASHES);
        if ($key === 'body_ref') {
            $text = basename($text);
        }

        $parts[] = $key . '=' . (mb_strlen($text) > 60 ? mb_substr($text, 0, 57) . '...' : $text);
    }

    return implode(' ', $parts);
}

/** @param array<string, mixed> $node */
function render(array $node, string $indent = ''): void
{
    // Events are recorded against the enclosing scope, so they belong under it, ahead of the
    // scopes it went on to open.
    $children = array_merge((array) ($node['events'] ?? []), (array) ($node['open'] ?? []));
    $last = count($children) - 1;
    foreach ($children as $i => $child) {
        $branch = $i === $last ? '└── ' : '├── ';
        $next = $indent . ($i === $last ? '    ' : '│   ');
        $close = $child['close'] ?? null;
        $arrow = is_array($close) ? ' → ' . $close['type'] . ' ' . summarize((array) $close['context']) : '';
        echo $indent, $branch, $child['type'], ' ', summarize((array) $child['context']), $arrow, "\n";
        render($child, $next);
    }
}

echo $file, "\n";
render($log);
