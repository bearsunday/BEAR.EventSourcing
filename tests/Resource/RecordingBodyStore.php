<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\EventSourcing\Resource\BodyStoreInterface;
use BEAR\Resource\AbstractRequest;
use BEAR\Resource\ResourceObject;

final class RecordingBodyStore implements BodyStoreInterface
{
    public int $calls = 0;

    /** @var non-empty-string|null */
    private readonly string|null $bodyRef;

    /** @param non-empty-string|null $bodyRef */
    public function __construct(string|null $bodyRef)
    {
        $this->bodyRef = $bodyRef;
    }

    public function __invoke(AbstractRequest $request, ResourceObject $ro): string|null
    {
        $this->calls++;

        return $this->bodyRef;
    }
}
