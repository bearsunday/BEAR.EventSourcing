<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Fake;

use BEAR\Resource\Request;
use BEAR\Resource\ResourceObject;

final class FakeRequest
{
    public static function create(
        ResourceObject $resourceObject,
        string $method = 'get',
        array $query = [],
    ): Request {
        return new Request(
            new FakeInvoker(),
            $resourceObject,
            $method,
            $query,
        );
    }
}
