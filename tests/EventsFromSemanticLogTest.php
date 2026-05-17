<?php

declare(strict_types=1);

namespace BEAR\EventSourcing;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;
use BEAR\SemanticLogger\SemanticLogger as BearSemanticLogger;
use Koriym\SemanticLogger\SemanticLogger;
use PHPUnit\Framework\TestCase;

final class EventsFromSemanticLogTest extends TestCase
{
    public function testExtractsEventsFromPostAndPut(): void
    {
        $koriymLogger = new SemanticLogger();
        $bridge = new BearSemanticLogger($koriymLogger);

        $bridge($this->makeResource('app://self/users', 'post', 201, ['id' => 1]));
        $bridge($this->makeResource('app://self/users/1', 'put', 200, ['updated' => true]));

        $events = Events::fromSemanticLog($koriymLogger->flush()->toArray());

        $this->assertCount(2, $events);
        $list = $events->all();
        $this->assertSame('app://self/users', $list[0]->uri);
        $this->assertSame('POST', $list[0]->method);
        $this->assertSame(['id' => 1], $list[0]->result);
        $this->assertSame('PUT', $list[1]->method);
    }

    public function testIgnoresGetEntries(): void
    {
        $koriymLogger = new SemanticLogger();
        $bridge = new BearSemanticLogger($koriymLogger);

        $bridge($this->makeResource('app://self/users/1', 'get', 200, ['id' => 1]));
        $bridge($this->makeResource('app://self/users', 'post', 201, []));

        $events = Events::fromSemanticLog($koriymLogger->flush()->toArray());

        $this->assertCount(1, $events);
        $this->assertSame('POST', $events->all()[0]->method);
    }

    public function testEmptyLogProducesEmptyCollection(): void
    {
        $events = Events::fromSemanticLog([]);

        $this->assertCount(0, $events);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function makeResource(string $uri, string $method, int $code, array|null $body): ResourceObject
    {
        $ro = new class extends ResourceObject {
        };
        $ro->uri = new Uri($uri);
        $ro->uri->method = $method;
        $ro->code = $code;
        $ro->body = $body;

        return $ro;
    }
}
