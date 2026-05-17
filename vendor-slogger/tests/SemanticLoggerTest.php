<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;
use Koriym\SemanticLogger\Exception\NoLogSessionException;
use Koriym\SemanticLogger\SemanticLogger as KoriymSemanticLogger;
use PHPUnit\Framework\TestCase;

final class SemanticLoggerTest extends TestCase
{
    private KoriymSemanticLogger $koriymLogger;
    private SemanticLogger $logger;

    protected function setUp(): void
    {
        $this->koriymLogger = new KoriymSemanticLogger();
        $this->logger = new SemanticLogger($this->koriymLogger);
    }

    public function testRecordsPostResource(): void
    {
        $ro = $this->makeResource('app://self/users', method: 'post', code: 201, body: ['id' => 1]);

        ($this->logger)($ro);

        $log = $this->koriymLogger->flush()->toArray();
        $this->assertCount(1, $log['open']);

        $open = $log['open'][0];
        $this->assertSame(ResourceRequestContext::TYPE, $open['type']);
        $this->assertSame('app://self/users', $open['context']['uri']);
        $this->assertSame('POST', $open['context']['method']);

        $this->assertArrayHasKey('close', $open);
        $this->assertSame(ResourceResponseContext::TYPE, $open['close']['type']);
        $this->assertSame(201, $open['close']['context']['code']);
        $this->assertSame(['id' => 1], $open['close']['context']['body']);
    }

    public function testSkipsGetResource(): void
    {
        $ro = $this->makeResource('app://self/users/1', method: 'get');

        ($this->logger)($ro);

        $this->expectException(NoLogSessionException::class);
        $this->koriymLogger->flush();
    }

    public function testRecordsPut(): void
    {
        $ro = $this->makeResource('app://self/users/1', method: 'put', code: 200, body: null);

        ($this->logger)($ro);

        $log = $this->koriymLogger->flush()->toArray();
        $this->assertSame('PUT', $log['open'][0]['context']['method']);
    }

    /**
     * @param array<string, mixed>|null $body
     */
    private function makeResource(string $uri, string $method, int $code = 200, array|null $body = null): ResourceObject
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
