<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Skill;

use PHPUnit\Framework\TestCase;

use function dirname;
use function escapeshellarg;
use function exec;
use function file_get_contents;
use function file_put_contents;
use function is_dir;
use function mkdir;
use function rmdir;
use function scandir;
use function sys_get_temp_dir;
use function unlink;
use function uniqid;

use const PHP_BINARY;

/**
 * The harness scripts run against applications this repository does not contain, so each test
 * builds the smallest application that carries the premise under test and runs the real script.
 */
final class ObserveHarnessTest extends TestCase
{
    private const COMPOSER_JSON = <<<'JSON'
        {
            "name": "my-vendor/my-project",
            "require-dev": {
                "bear/event-sourcing": "1.x-dev",
                "bear/query-repository": "1.x-dev"
            },
            "autoload": {
                "psr-4": {
                    "MyVendor\\MyProject\\": "src/"
                }
            }
        }
        JSON;

    private string $appDir = '';

    protected function setUp(): void
    {
        $this->appDir = sys_get_temp_dir() . '/observe-harness-' . uniqid();
        // A skeleton ships both directories, so setup.php writes into them rather than creating them.
        mkdir($this->appDir . '/bin', 0755, true);
        mkdir($this->appDir . '/src/Module', 0755, true);
        $this->write('composer.json', self::COMPOSER_JSON);
    }

    protected function tearDown(): void
    {
        self::remove($this->appDir);
    }

    public function testSetupRequiresVendorAutoloadWhenRootAutoloadIsAbsent(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $devBin = $this->read('bin/dev.php');
        $this->assertStringContainsString("require dirname(__DIR__) . '/vendor/autoload.php';", $devBin);
        $this->assertStringNotContainsString("'/autoload.php'", $devBin);
    }

    public function testSetupPrefersTheRootAutoloadWhenTheApplicationHasOne(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");
        $this->write('autoload.php', "<?php\nrequire __DIR__ . '/vendor/autoload.php';\n");

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString(
            "require dirname(__DIR__) . '/autoload.php';",
            $this->read('bin/dev.php'),
        );
    }

    public function testCheckFailsWithoutAnyAutoloadInsteadOfCrashing(): void
    {
        $empty = $this->appDir . '/empty';
        mkdir($empty, 0755, true);

        [$status, $output] = $this->runHarness('check.php', $empty);

        $this->assertSame(1, $status);
        $this->assertStringContainsString('FAIL  no autoload', $output);
        $this->assertStringNotContainsString('Fatal error', $output);
    }

    /**
     * @return array{0: int, 1: string}
     */
    private function runHarness(string $script, string ...$args): array
    {
        $command = PHP_BINARY . ' ' . escapeshellarg(dirname(__DIR__, 2) . '/skills/bear-observe/harness/' . $script);
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

    private function write(string $path, string $contents): void
    {
        $file = $this->appDir . '/' . $path;
        $dir = dirname($file);
        is_dir($dir) || mkdir($dir, 0755, true);
        file_put_contents($file, $contents);
    }

    private function read(string $path): string
    {
        $file = $this->appDir . '/' . $path;
        $contents = file_get_contents($file);
        $this->assertNotFalse($contents, "{$path} was not written");

        return $contents;
    }

    private static function remove(string $path): void
    {
        if (! is_dir($path)) {
            unlink($path);

            return;
        }

        $entries = scandir($path);
        foreach ($entries === false ? [] : $entries as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            self::remove($path . '/' . $entry);
        }

        rmdir($path);
    }
}
