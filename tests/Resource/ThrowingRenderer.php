<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Resource;

use BEAR\Resource\RenderInterface;
use BEAR\Resource\ResourceObject;
use RuntimeException;

final class ThrowingRenderer implements RenderInterface
{
    public function render(ResourceObject $ro): string
    {
        throw new RuntimeException('The view must not be rendered.');
    }
}
