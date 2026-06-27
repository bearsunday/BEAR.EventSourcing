<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\BodyStoreException;
use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

final class ThrowingBodyStore implements BodyStoreInterface
{
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        throw new BodyStoreException('The body store failed.');
    }
}
