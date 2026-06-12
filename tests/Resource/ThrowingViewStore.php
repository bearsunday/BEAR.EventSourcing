<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ViewStoreException;
use BEAR\EventSourcing\Resource\ViewStoreInterface;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

final class ThrowingViewStore implements ViewStoreInterface
{
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        throw new ViewStoreException('The view store failed.');
    }
}
