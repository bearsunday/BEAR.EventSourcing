<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use Override;

/** An invoker of the application's own */
final class AppInvoker implements InvokerInterface
{
    #[Override]
    public function invoke(AbstractRequest $request): ResourceObject
    {
        return $request->resourceObject;
    }
}
