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
        $this->write('composer.json', self::COMPOSER_JSON);
    }

    protected function tearDown(): void
    {
        self::remove($this->appDir);
    }

    public function testSetupTakesTheContextFromTheGivenEntryPoint(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('prod-hal-app', \$GLOBALS, \$_SERVER));\n");
        $this->write('bin/admin.php', "<?php\nexit((new Bootstrap())('prod-admin-app', \$GLOBALS, \$_SERVER));\n");

        [$status, $output] = $this->runHarness('setup.php', $this->appDir, 'bin/admin.php');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('entry      bin/admin.php', $output);
        $this->assertStringContainsString('context    cli-dev-admin-app', $output);
        $this->assertStringContainsString("'cli-dev-admin-app'", $this->read('bin/dev.php'));
    }

    public function testSetupFailsOnAMissingNamedEntryPointBeforeWritingAnything(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");

        [$status, $output] = $this->runHarness('setup.php', $this->appDir, 'bin/admin.php');

        $this->assertSame(1, $status, $output);
        $this->assertMatchesRegularExpression('#^FAIL  no entry point at .+/bin/admin\.php$#m', $output);
        $this->assertFileDoesNotExist($this->appDir . '/src/Module/DevModule.php');
        $this->assertFileDoesNotExist($this->appDir . '/bin/dev.php');
    }

    public function testSetupStripsSapiAndEnvironmentPrefixes(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('cli-hal-app', \$GLOBALS, \$_SERVER));\n");

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('context    cli-dev-hal-app', $output);
        $this->assertStringNotContainsString('cli-dev-cli-', $output);
    }

    public function testSetupStripsTheWholeRunOfPrefixes(): void
    {
        $this->write(
            'public/index.php',
            "<?php\nexit((new Bootstrap())('prod-cli-dev-hal-app', \$GLOBALS, \$_SERVER));\n",
        );

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('context    cli-dev-hal-app', $output);
        $this->assertStringContainsString("'cli-dev-hal-app'", $this->read('bin/dev.php'));
    }

    public function testSetupNotesAKeptDevPhpThatRunsAnotherContext(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");
        $this->write('bin/admin.php', "<?php\nexit((new Bootstrap())('admin-app', \$GLOBALS, \$_SERVER));\n");
        $this->runHarness('setup.php', $this->appDir);

        [$status, $output] = $this->runHarness('setup.php', $this->appDir, 'bin/admin.php');

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString('bin/dev.php (--force to overwrite)', $output);
        $this->assertStringContainsString('note       bin/dev.php runs another context', $output);
        $this->assertStringContainsString("'cli-dev-hal-app'", $this->read('bin/dev.php'));
    }

    public function testSetupWarnsWhenAnExistingDevModuleAlreadyRebindsBecomingInterface(): void
    {
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");
        $this->write('src/Module/DevModule.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace MyVendor\MyProject\Module;

            use Be\Framework\BecomingInterface;
            use Ray\Di\AbstractModule;

            final class DevModule extends AbstractModule
            {
                protected function configure(): void
                {
                    $this->bind(BecomingInterface::class)->toProvider(DevBecomingProvider::class);
                }
            }
            PHP);

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString(
            'warning    src/Module/DevModule.php already exists — confirm whether it',
            $output,
        );
        $this->assertFileExists($this->appDir . '/src/Module/DevModule.php.observe');
    }

    public function testSetupWarnsEvenWhenTheSharedLoggerIsWiredThroughAnotherClass(): void
    {
        // BeMart's actual shape: DevModule itself never mentions BecomingInterface — it
        // overrides a *different* module that does. A same-file string search for
        // "BecomingInterface" would miss this exact case, so the warning fires on the merge
        // path itself, not on a content match.
        $this->write('public/index.php', "<?php\nexit((new Bootstrap())('hal-app', \$GLOBALS, \$_SERVER));\n");
        $this->write('src/Module/DevModule.php', <<<'PHP'
            <?php

            declare(strict_types=1);

            namespace MyVendor\MyProject\Module;

            use Ray\Di\AbstractModule;

            final class DevModule extends AbstractModule
            {
                protected function configure(): void
                {
                    $this->override(new DevLoggingModule());
                }
            }
            PHP);

        [$status, $output] = $this->runHarness('setup.php', $this->appDir);

        $this->assertSame(0, $status, $output);
        $this->assertStringContainsString(
            'warning    src/Module/DevModule.php already exists — confirm whether it',
            $output,
        );
        $this->assertFileExists($this->appDir . '/src/Module/DevModule.php.observe');
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
