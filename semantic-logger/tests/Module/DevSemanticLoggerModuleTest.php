<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Module\ResourceClientModule;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Invoker\DevSemanticInvoker;
use BEAR\SemanticLogger\Profile\Compact\ContextFactory;
use Koriym\SemanticLogger\DevLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function glob;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;
use function unlink;

final class DevSemanticLoggerModuleTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/semantic-logger-test-' . uniqid();
        mkdir($this->tempDir);
    }

    protected function tearDown(): void
    {
        $files = glob($this->tempDir . '/*');
        if ($files !== false) {
            foreach ($files as $file) {
                unlink($file);
            }
        }

        rmdir($this->tempDir);
    }

    public function testBindSemanticLogger(): void
    {
        $injector = new Injector(new DevSemanticLoggerModule($this->tempDir, null));

        $logger = $injector->getInstance(SemanticLoggerInterface::class);

        $this->assertInstanceOf(SemanticLoggerInterface::class, $logger);
    }

    public function testBindDevLogger(): void
    {
        $injector = new Injector(new DevSemanticLoggerModule($this->tempDir, null));

        $devLogger = $injector->getInstance(DevLogger::class);

        $this->assertInstanceOf(DevLogger::class, $devLogger);
    }

    public function testBindContextFactory(): void
    {
        $injector = new Injector(new DevSemanticLoggerModule($this->tempDir, null));

        $factory = $injector->getInstance(ContextFactoryInterface::class);

        $this->assertInstanceOf(ContextFactory::class, $factory);
    }

    public function testBindInvoker(): void
    {
        // Test with ResourceClientModule to provide Invoker dependencies
        $tempDir = $this->tempDir;
        $module = new class ($tempDir) extends AbstractModule {
            public function __construct(private readonly string $tempDir)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $this->install(new ResourceClientModule());
                $this->override(new DevSemanticLoggerModule($this->tempDir));
            }
        };
        $injector = new Injector($module);

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(DevSemanticInvoker::class, $invoker);
    }

    public function testDefaultLogDir(): void
    {
        $injector = new Injector(new DevSemanticLoggerModule(null, null));

        $devLogger = $injector->getInstance(DevLogger::class);

        $this->assertInstanceOf(DevLogger::class, $devLogger);
    }
}
