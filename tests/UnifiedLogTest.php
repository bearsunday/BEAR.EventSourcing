<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests;

use BEAR\EventSourcing\RecordedMethods;
use BEAR\EventSourcing\Resource\DevLogModule;
use BEAR\EventSourcing\Resource\FileBodyStore;
use BEAR\EventSourcing\SemanticLogExtractor;
use BEAR\EventSourcing\Tests\Fixture\UnifiedLogModule;
use BEAR\Resource\ResourceInterface;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;
use Ray\Di\Injector;

use function is_array;
use function json_decode;
use function json_encode;
use function mkdir;
use function rmdir;
use function sys_get_temp_dir;
use function uniqid;

use const JSON_THROW_ON_ERROR;

/**
 * @psalm-suppress NoInterfaceProperties,MixedFunctionCall,MixedMethodCall,MixedAssignment
 *     The resource client is driven through its magic verb properties.
 */
final class UnifiedLogTest extends TestCase
{
    public function testCacheScopesNestInsideResourceRequestTree(): void
    {
        $logger = new SemanticLogger();
        $bodyDir = sys_get_temp_dir() . '/' . uniqid('bear-es-unified-bodies-', true);
        mkdir($bodyDir);
        $storageDir = sys_get_temp_dir() . '/' . uniqid('bear-es-unified-storage-', true);
        mkdir($storageDir);

        $injector = new Injector(new DevLogModule(
            bodyDir: $bodyDir,
            logger: $logger,
            module: new UnifiedLogModule($logger, $storageDir),
        ));
        $resource = $injector->getInstance(ResourceInterface::class);

        $resource->get->uri('app://self/greeting')(['name' => 'koriym']); // miss + save
        $resource->get->uri('app://self/greeting')(['name' => 'koriym']); // hit

        $log = $logger->flush();
        /** @var array{open: list<array<string, mixed>>} $tree */
        $tree = json_decode(json_encode($log, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
        $roots = $tree['open'];
        $this->assertNotSame([], $roots);
        foreach ($roots as $root) {
            $this->assertSame('resource_request', $root['type']);
        }

        // The cache scope sits inside a resource_request scope: one request, one tree.
        $this->assertTrue(self::subtreeHasType($roots, 'get'));

        // Type-gated extraction: cache scopes in the same tree are never
        // misread as state changes, so events stay purely resource facts.
        $events = (new SemanticLogExtractor(new RecordedMethods(RecordedMethods::WITH_READS)))->extract($log);
        $this->assertCount(2, $events);

        FileBodyStore::clearDirectory($bodyDir);
        rmdir($bodyDir);
    }

    /** @param array<array-key, mixed> $entries */
    private static function subtreeHasType(array $entries, string $type): bool
    {
        foreach ($entries as $entry) {
            if (! is_array($entry)) {
                continue;
            }

            if (($entry['type'] ?? null) === $type) {
                return true;
            }

            foreach (['open', 'events'] as $childKey) {
                $children = $entry[$childKey] ?? [];
                if (is_array($children) && self::subtreeHasType($children, $type)) {
                    return true;
                }
            }
        }

        return false;
    }
}
