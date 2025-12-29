<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Fake;

use BEAR\Resource\AbstractRequest;
use BEAR\Resource\InvokerInterface;
use BEAR\Resource\ResourceObject;

class FakeInvoker implements InvokerInterface
{
    public function invoke(AbstractRequest $request): ResourceObject
    {
        $method = 'on' . ucfirst(strtolower($request->method));
        $ro = $request->resourceObject;

        if (method_exists($ro, $method)) {
            $ro->{$method}(...array_values($request->query));
        }

        return $ro;
    }
}
