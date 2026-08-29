<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Module\ResourceClientModule;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function file_exists;
use function file_put_contents;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;

final class ResourceObservationModuleTest extends TestCase
{
    public function testDecoratesResourceInvoker(): void
    {
        $injector = new Injector(new ResourceObservationModule(module: new ResourceClientModule()));

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(SemanticLogInvoker::class, $invoker);
    }

    public function testDevModuleClearsDirectoryAndUsesFileBodyStoreWithReads(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-dev-bodies-', true);
        mkdir($dir);
        new FileBodyStore($dir); // a prior dev run adopts the directory (writes the ownership marker)
        file_put_contents($dir . '/old.json', '{}');

        $injector = new Injector(new DevLogModule(
            bodyDir: $dir,
            module: new ResourceClientModule(),
        ));

        $this->assertFalse(file_exists($dir . '/old.json'));
        $this->assertInstanceOf(SemanticLogInvoker::class, $injector->getInstance(InvokerInterface::class));
        $this->assertInstanceOf(FileBodyStore::class, $injector->getInstance(BodyStoreInterface::class));
        $this->assertSame('GET', $injector->getInstance(RecordedMethods::class, Recorded::class)->normalize('GET'));

        FileBodyStore::clearDirectory($dir);
        rmdir($dir);
    }
}
