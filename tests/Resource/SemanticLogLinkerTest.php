<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\ResourceObservationModule;
use BEAR\EventSourcing\Resource\SemanticLogLinker;
use BEAR\EventSourcing\Tests\Fixture\Link\LinkAppModule;
use BEAR\Resource\Exception\LinkRelException;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_column;
use function array_diff_key;
use function json_decode;
use function json_encode;

use const JSON_THROW_ON_ERROR;

/** @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design. */
final class SemanticLogLinkerTest extends TestCase
{
    public function testResourceObservationModuleDecoratesTheLinker(): void
    {
        $injector = new Injector(new ResourceObservationModule(module: new LinkAppModule()));

        $this->assertInstanceOf(SemanticLogLinker::class, $injector->getInstance(LinkerInterface::class));
    }

    public function testTheRequestsMadeToFollowTheLinksNestInOneLinkRequest(): void
    {
        $logger = new SemanticLogger();
        $ro = $this->resource($logger)->uri('page://self/form')->linkSelf('goItem')();
        $this->assertSame(['id' => '7'], $ro->body);

        $root = $this->roots($logger)[0];
        $this->assertSame('link_request', $root['type']);
        $this->assertSame(
            ['uri' => 'page://self/form', 'method' => 'GET', 'links' => ['self:goItem']],
            array_diff_key($root['context'], ['timestamp' => true]),
        );
        $requested = array_column(array_column($root['log'], 'context'), 'uri');
        $this->assertSame(['page://self/form', 'app://self/item'], $requested);
        $this->assertSame(['code' => 200], array_diff_key($root['close']['context'], ['durationMs' => true]));
    }

    public function testALinkThatCannotBeFollowedClosesTheLinkRequestWithItsException(): void
    {
        $logger = new SemanticLogger();
        try {
            $this->resource($logger)->uri('page://self/form')->linkSelf('missing')();
            $this->fail('LinkRelException should be thrown.');
        } catch (LinkRelException) {
        }

        $close = $this->roots($logger)[0]['close']['context'];
        $this->assertSame(LinkRelException::class, $close['exception']['class']);
        $this->assertArrayNotHasKey('code', $close);
    }

    private function resource(SemanticLogger $logger): ResourceInterface
    {
        $module = new ResourceObservationModule(
            methods: new RecordedMethods(RecordedMethods::WITH_READS),
            logger: $logger,
            module: new LinkAppModule(),
        );

        return (new Injector($module))->getInstance(ResourceInterface::class);
    }

    /** @return list<array<string, mixed>> */
    private function roots(SemanticLogger $logger): array
    {
        /** @var array{log: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $tree['log'];
    }
}
