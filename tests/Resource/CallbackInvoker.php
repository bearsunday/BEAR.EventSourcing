<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;
use Closure;

final readonly class CallbackInvoker implements InvokerInterface
{
    /** @var Closure(AbstractRequest): ResourceObject */
    private Closure $callback;

    public function __construct(callable $callback)
    {
        /** @var Closure(AbstractRequest): ResourceObject $closure */
        $closure = $callback(...);
        $this->callback = $closure;
    }

    public function invoke(AbstractRequest $request): ResourceObject
    {
        return ($this->callback)($request);
    }
}
