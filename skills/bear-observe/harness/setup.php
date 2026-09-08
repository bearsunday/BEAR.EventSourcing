#!/usr/bin/env php
<?php

declare(strict_types=1);

/**
 * Installs the observation context into a BEAR.Sunday application.
 *
 * Usage: php setup.php [app-dir] [--force]
 *
 * Writes src/Module/{DevModule,ObserveLoggerProvider,DevPoolProvider}.php and bin/dev.php.
 * Existing files are left alone unless --force. Nothing else in the application changes:
 * the flush is the cache log module's shutdown sink, not a line in public/index.php.
 */

$argvList = $argv;
array_shift($argvList);
$force = in_array('--force', $argvList, true);
$appDir = realpath($argvList[0] ?? getcwd()) ?: '';
$templateDir = dirname(__DIR__) . '/templates';

function fail(string $message): never
{
    fwrite(STDERR, "FAIL  {$message}\n");
    exit(1);
}

$composerFile = $appDir . '/composer.json';
is_file($composerFile) || fail("no composer.json in {$appDir}");
$composer = json_decode((string) file_get_contents($composerFile), true);
is_array($composer) || fail("unreadable composer.json in {$appDir}");

$namespace = '';
foreach (($composer['autoload']['psr-4'] ?? []) as $prefix => $dir) {
    $dirs = (array) $dir;
    if (in_array('src', array_map(static fn (string $d): string => rtrim($d, '/'), $dirs), true)) {
        $namespace = rtrim((string) $prefix, '\\');
        break;
    }
}
$namespace !== '' || fail('no psr-4 prefix maps to src/ — is this a BEAR.Sunday application?');

$required = ['bear/event-sourcing', 'bear/query-repository'];
$missing = array_values(array_filter(
    $required,
    static fn (string $p): bool => ! isset($composer['require'][$p]) && ! isset($composer['require-dev'][$p]),
));
if ($missing !== []) {
    fail('missing dependencies: ' . implode(' ', $missing) . "\n      run: composer require --dev " . implode(':1.x-dev ', $missing) . ':1.x-dev');
}

$written = [];
$kept = [];
$merge = [];
// A marker per class: the identifier that must appear for the file to be doing our job already.
$markers = [
    'DevModule' => 'SemanticLogInvoker',
    'ObserveLoggerProvider' => 'CacheLog',
    'DevPoolProvider' => 'FilesystemAdapter',
];
foreach ($markers as $class => $marker) {
    $target = $appDir . '/src/Module/' . $class . '.php';
    $source = str_replace('__NAMESPACE__', $namespace, (string) file_get_contents($templateDir . '/' . $class . '.php'));

    if (is_file($target) && ! $force) {
        // An application's own module already carries bindings this cannot guess at, so the
        // template lands beside it and the merge is a reading job, not a rewrite.
        if (! str_contains((string) file_get_contents($target), $marker)) {
            file_put_contents($target . '.observe', $source);
            $merge[] = 'src/Module/' . $class . '.php';
            continue;
        }

        $kept[] = 'src/Module/' . $class . '.php';
        continue;
    }

    is_dir(dirname($target)) || mkdir(dirname($target), 0755, true);
    file_put_contents($target, $source);
    $written[] = 'src/Module/' . $class . '.php';
}

// The observation context is the application's own web context with `dev-` in front of it,
// so DevModule joins the chain the application actually runs rather than a second one.
$entry = $appDir . '/public/index.php';
$appContext = is_file($entry) && preg_match("/'(?:prod-|dev-)?([a-z0-9-]*app)'/", (string) file_get_contents($entry), $m) === 1
    ? $m[1]
    : 'app';
$context = 'cli-dev-' . $appContext;

$devBin = $appDir . '/bin/dev.php';
if (! is_file($devBin) || $force) {
    file_put_contents($devBin, <<<PHP
    <?php

    declare(strict_types=1);

    use {$namespace}\\Bootstrap;

    require dirname(__DIR__) . '/autoload.php';
    exit((new Bootstrap())('{$context}', \$GLOBALS, \$_SERVER));

    PHP);
    $written[] = 'bin/dev.php';
} else {
    $kept[] = 'bin/dev.php';
}

echo "namespace  {$namespace}\n";
echo "context    {$context}\n";
echo "log        var/log/{$context}/observe/latest.json\n";
$written === [] || print('written    ' . implode(' ', $written) . "\n");
$kept === [] || print('kept       ' . implode(' ', $kept) . " (--force to overwrite)\n");
$merge === [] || print("merge      " . implode(' ', $merge) . " already exist — the observation bindings are in the sibling .observe file; fold them in by hand\n");
echo 'next       php ' . dirname(__DIR__) . "/harness/check.php {$appDir} {$context}\n";
