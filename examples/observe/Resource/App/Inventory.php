<?php

declare(strict_types=1);

namespace FakeApp\Resource\App;

use BEAR\Resource\ResourceObject;

final class Inventory extends ResourceObject
{
    public function onPut(string $sku, int $quantity): static
    {
        $this->body = ['sku' => $sku, 'reserved' => $quantity];

        return $this;
    }
}
