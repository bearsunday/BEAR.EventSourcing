<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;

final class FakeResourceObject extends ResourceObject
{
    public function __construct(
        string $uri = 'app://self/resource',
        mixed $body = null,
        int $code = 200,
    ) {
        $this->uri = new Uri($uri);
        $this->body = $body;
        $this->code = $code;
    }
}
