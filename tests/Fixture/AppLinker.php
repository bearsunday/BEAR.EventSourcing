<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\LinkerInterface;
use BEAR\Resource\ResourceObject;
use Override;

/** A linker of the application's own, as a test binding BEAR.DevTools' DeclaredMethodLinker has */
final class AppLinker implements LinkerInterface
{
    #[Override]
    public function invoke(AbstractRequest $request): ResourceObject
    {
        return $request->resourceObject;
    }
}
