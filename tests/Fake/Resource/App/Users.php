<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Fake\Resource\App;

use BEAR\Resource\ResourceObject;

class Users extends ResourceObject
{
    public function onGet(int $id = 0): static
    {
        $this->code = 200;
        $this->body = ['id' => $id];

        return $this;
    }

    public function onPost(string $name, int $age): static
    {
        $this->code = 201;
        $this->body = ['name' => $name, 'age' => $age];

        return $this;
    }
}
