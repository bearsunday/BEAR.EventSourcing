<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Filtered;
use BEAR\EventSourcing\Recorded;
use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\Resource\FilteredParams;
use BEAR\EventSourcing\Resource\NullBodyStore;
use BEAR\EventSourcing\Resource\ParamsFilterInterface;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogInvoker;
use BEAR\EventSourcing\Resource\SensitiveParamsFilter;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\Method;
use BEAR\Resource\Module\ResourceClientModule;
use BEAR\Resource\Request;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\AbstractModule;
use Ray\Di\Injector;

use function is_dir;
use function json_decode;
use function json_encode;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 */
final class ResourceObservationModuleTest extends TestCase
{
    public function testDecoratesResourceInvoker(): void
    {
        $injector = new Injector(new ResourceObservationModule(module: new ResourceClientModule()));

        $invoker = $injector->getInstance(InvokerInterface::class);

        $this->assertInstanceOf(SemanticLogInvoker::class, $invoker);
    }

    public function testDefaultParamsFilterResolvesThroughTheRealInjector(): void
    {
        // #[Filtered] ParamsFilterInterface is nullable and unbound resolves through
        // SemanticLogInvoker's own PHP default, but this module also binds it explicitly
        // (matching #[Recorded] RecordedMethods) so the graph never depends on Ray.Di's
        // unbound-nullable fallback to reach SensitiveParamsFilter.
        $injector = new Injector(new ResourceObservationModule(module: new ResourceClientModule()));

        $filter = $injector->getInstance(ParamsFilterInterface::class, Filtered::class);

        $this->assertInstanceOf(SensitiveParamsFilter::class, $filter);
    }

    public function testDevModuleDefersDirectoryCreationAndUsesFileBodyStoreWithReads(): void
    {
        $dir = sys_get_temp_dir() . '/' . uniqid('bear-es-dev-bodies-', true);

        $injector = new Injector(new DevLogModule(
            bodyDir: $dir,
            module: new ResourceClientModule(),
        ));

        // Constructing the module costs nothing until a body is actually stored:
        // no eager mkdir/clear that would drift out of sync with what gets logged.
        $this->assertFalse(is_dir($dir));
        $this->assertInstanceOf(SemanticLogInvoker::class, $injector->getInstance(InvokerInterface::class));
        $this->assertInstanceOf(FileBodyStore::class, $injector->getInstance(BodyStoreInterface::class));
        $this->assertSame('GET', $injector->getInstance(RecordedMethods::class, Recorded::class)->normalize('GET'));
    }

    public function testCustomParamsFilterBoundByTheApplicationReachesTheRealInvoker(): void
    {
        // The documented opt-out path (README "Redacting sensitive params"): an application
        // binds its own #[Filtered] ParamsFilterInterface via the paramsFilter constructor
        // parameter. A qualifier binding resolving in isolation (the test above) does not
        // prove the toConstructor name-map entry actually delivers that instance into
        // SemanticLogInvoker on a real DI-built invoker — this drives an actual invocation
        // through the module and reads the recorded params back out of the log.
        $customFilter = new class implements ParamsFilterInterface {
            /** @param array<string, mixed> $params */
            public function __invoke(array $params): FilteredParams
            {
                unset($params['otp']);

                return new FilteredParams($params, replayable: false);
            }
        };

        $ro = new FakeResourceObject('app://self/verify');
        $base = new class ($ro) extends AbstractModule {
            public function __construct(private readonly FakeResourceObject $ro)
            {
                parent::__construct();
            }

            protected function configure(): void
            {
                $ro = $this->ro;
                $this->bind(InvokerInterface::class)->toInstance(
                    new CallbackInvoker(static fn (): FakeResourceObject => $ro),
                );
            }
        };

        $logger = new SemanticLogger();
        $injector = new Injector(new ResourceObservationModule(
            logger: $logger,
            paramsFilter: $customFilter,
            module: $base,
        ));

        $invoker = $injector->getInstance(InvokerInterface::class);
        $invoker->invoke(new Request(
            new CallbackInvoker(static fn (): FakeResourceObject => $ro),
            $ro,
            Method::POST,
            ['otp' => '123456'],
        ));

        /** @var array{open: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $params = $tree['open'][0]['context']['params'];
        $this->assertArrayNotHasKey(
            'otp',
            $params,
            'a filter bound via ResourceObservationModule(paramsFilter: ...) must reach SemanticLogInvoker',
        );
        $this->assertFalse($tree['open'][0]['context']['replayable']);
    }
}
