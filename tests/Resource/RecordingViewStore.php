<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\ViewStoreInterface;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

final class RecordingViewStore implements ViewStoreInterface
{
    public int $calls = 0;

    /** @var non-empty-string|null */
    private readonly string|null $viewRef;

    /** @param non-empty-string|null $viewRef */
    public function __construct(string|null $viewRef)
    {
        $this->viewRef = $viewRef;
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        $this->calls++;

        return $this->viewRef;
    }
}
