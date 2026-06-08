<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\EventSourcing\EventSourcing\Event;
use BEAR\EventSourcing\EventSourcing\EventStoreInterface;
use BEAR\Resource\LoggerInterface;
use BEAR\Resource\ResourceObject;

use function in_array;
use function sprintf;
use function strtoupper;

/**
 * Records completed state-changing resource requests as events.
 */
final readonly class EventSourcingLogger implements LoggerInterface
{
    private const RECORDED_METHODS = ['post', 'put', 'delete'];

    public function __construct(
        private EventStoreInterface $eventStore,
        private LoggerInterface $logger,
    ) {
    }

    public function __invoke(ResourceObject $ro): void
    {
        $method = $ro->uri->method;
        if (in_array($method, self::RECORDED_METHODS, true)) {
            $this->eventStore->append(Event::create(
                $this->resourceUri($ro),
                strtoupper($method),
                $ro->uri->query,
                $ro->body,
            ));
        }

        ($this->logger)($ro);
    }

    private function resourceUri(ResourceObject $ro): string
    {
        return sprintf('%s://%s%s', $ro->uri->scheme, $ro->uri->host, $ro->uri->path);
    }
}
