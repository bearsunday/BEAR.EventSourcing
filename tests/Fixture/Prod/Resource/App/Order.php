<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Prod\Resource\App;

use BEAR\Resource\ResourceObject;

final class Order extends ResourceObject
{
    public function onGet(string $orderId): static
    {
        $this->body = ['orderId' => $orderId];

        return $this;
    }

    public function onPost(string $orderId): static
    {
        $this->code = 201;
        $this->body = ['orderId' => $orderId];

        return $this;
    }

    public function onDelete(string $orderId): static
    {
        $this->code = 409;
        $this->body = ['orderId' => $orderId];

        return $this;
    }
}
