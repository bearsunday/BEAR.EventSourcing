<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

interface ViewStoreInterface
{
    /**
     * Store a resource view and return its reference.
     *
     * @return non-empty-string|null
     */
    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null;
}
