<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Tests\Fixture\Embed\EmbedAppModule;
use BEAR\EventSourcing\Tests\Fixture\Embed\Trace;
use BEAR\EventSourcing\Tests\RemovesDirectoryTree;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function array_column;
use function array_map;
use function assert;
use function file_get_contents;
use function json_decode;
use function json_encode;
use function parse_url;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;
use const PHP_URL_PATH;

/**
 * Observing an html app with DevLogModule must not change what the app runs or when.
 *
 * @psalm-suppress MixedAssignment,MixedArrayAccess,MixedArgument The canonical JSON view is untyped by design.
 * @psalm-suppress NoInterfaceProperties,MixedFunctionCall,MixedMethodCall
 *     The resource client is driven through its magic verb properties.
 */
final class EmbeddedRequestObservationTest extends TestCase
{
    use RemovesDirectoryTree;

    private string $bodyDir = '';
    private string $html = '';

    protected function setUp(): void
    {
        Trace::$events = [];
        $this->bodyDir = sys_get_temp_dir() . '/' . uniqid('bear-es-embed-', true);
    }

    protected function tearDown(): void
    {
        self::removeTree($this->bodyDir);
    }

    public function testEmbeddedRequestRunsOnlyWhenTheAppRenders(): void
    {
        $this->renderIndexPage();

        $this->assertSame([
            '--- app renders',
            'render page://self/index',
            'run app://self/child?id=lazy',
        ], Trace::$events);
        $this->assertSame('<p>title="index"</p><p>child={"id":"lazy"}</p>', $this->html);
    }

    public function testEmbeddedRequestIsARootOfItsOwnAndNoResponseCarriesAnException(): void
    {
        $roots = $this->renderIndexPage();

        $uris = array_column(array_column($roots, 'context'), 'uri');
        $this->assertSame(['page://self/index', 'app://self/child'], $uris);
        foreach ($roots as $root) {
            $this->assertArrayNotHasKey('log', $root);
            $this->assertArrayNotHasKey('exception', $root['close']['context']);
        }
    }

    public function testStoredPageBodyKeepsTheEmbeddedRequestUnrun(): void
    {
        $roots = $this->renderIndexPage();

        $this->assertSame([
            '{"title":"index","child":{"$request":{"method":"GET","uri":"app://self/child","params":{"id":"lazy"}}}}',
            '{"id":"lazy"}',
        ], array_map(self::stored(...), $roots));
    }

    /** @return list<array<string, mixed>> the flushed log roots */
    private function renderIndexPage(): array
    {
        $logger = new SemanticLogger();
        $resource = (new Injector(new DevLogModule(
            bodyDir: $this->bodyDir,
            logger: $logger,
            module: new EmbedAppModule(),
        )))->getInstance(ResourceInterface::class);

        $page = $resource->get->uri('page://self/index')();
        assert($page instanceof ResourceObject);
        Trace::$events[] = '--- app renders';
        $this->html = $page->toString();

        /** @var array{log: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($logger->flush(), JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);

        return $tree['log'];
    }

    /** @param array<string, mixed> $root */
    private static function stored(array $root): string
    {
        return (string) file_get_contents((string) parse_url($root['close']['context']['body_ref'], PHP_URL_PATH));
    }
}
