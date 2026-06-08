<?php

declare(strict_types=1);

namespace BEAR\SemanticLogger\Fake;

use BEAR\Resource\ResourceObject;
use BEAR\Resource\Uri;

final class FakeResourceObject extends ResourceObject
{
    public function __construct()
    {
        $this->uri = new Uri('app://self/user');
        $this->code = 200;
        $this->headers = ['Content-Type' => 'application/json'];
        $this->body = ['id' => 1, 'name' => 'test'];
    }

    public function onGet(int $id): static
    {
        $this->body = ['id' => $id, 'name' => 'test'];

        return $this;
    }
}
