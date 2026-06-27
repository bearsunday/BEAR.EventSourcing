<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

final class NullBodyStore implements BodyStoreInterface
{
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        return null;
    }
}
