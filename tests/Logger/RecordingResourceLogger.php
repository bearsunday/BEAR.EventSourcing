<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Logger;

use BEAR\Resource\LoggerInterface;
use BEAR\Resource\ResourceObject;

final class RecordingResourceLogger implements LoggerInterface
{
    /** @var list<ResourceObject> */
    public array $resources = [];

    public function __invoke(ResourceObject $ro): void
    {
        $this->resources[] = $ro;
    }
}
