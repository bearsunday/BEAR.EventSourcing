<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Interceptor;

use BEAR\Resource\ResourceObject;

final class NullParameterResource extends ResourceObject
{
    public function onPatch(string|null $memo = null): static
    {
        $this->body = ['memo' => $memo];

        return $this;
    }
}
