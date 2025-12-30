<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Module;

use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Module\ResourceClientModule;
use BEAR\SemanticLogger\Context\ContextFactoryInterface;
use BEAR\SemanticLogger\Invoker\DevSemanticInvoker;
use BEAR\SemanticLogger\Profile\Verbose\ContextFactory;
use Koriym\SemanticLogger\DevLogger;
use Koriym\SemanticLogger\SemanticLoggerInterface;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

final class VerboseSemanticLoggerModuleTest extends TestCase
{
    private string $tempDir;

    protected function setUp(): void
    {
        $this->tempDir = sys_get_temp_dir() . '/verbose-logger-test-' . uniqid();
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
        $injector = new Injector(new VerboseSemanticLoggerModule($this->tempDir));

        $logger = $injector->getInstance(SemanticLoggerInterface::class);

        $this->assertInstanceOf(SemanticLoggerInterface::class, $logger);
    }

    public function testBindDevLogger(): void
    {
        $injector = new Injector(new VerboseSemanticLoggerModule($this->tempDir));

        $devLogger = $injector->getInstance(DevLogger::class);

        $this->assertInstanceOf(DevLogger::class, $devLogger);
    }

    public function testBindVerboseContextFactory(): void
    {
        $injector = new Injector(new VerboseSemanticLoggerModule($this->tempDir));

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
                $this->override(new VerboseSemanticLoggerModule($this->tempDir));
            }
        };
        $injector = new Injector($module);

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(DevSemanticInvoker::class, $invoker);
    }

    public function testDefaultLogDir(): void
    {
        $injector = new Injector(new VerboseSemanticLoggerModule());

        $devLogger = $injector->getInstance(DevLogger::class);

        $this->assertInstanceOf(DevLogger::class, $devLogger);
    }
}
