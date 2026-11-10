<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Tests\Fixture\Caller\Resource\Page;

use BEAR\EventSourcing\Tests\Fixture\Caller\SiteSetting;
use BEAR\Resource\ResourceInterface;
use BEAR\Resource\ResourceObject;

/** @psalm-suppress NoInterfaceProperties,MixedMethodCall The resource client is driven through its magic verb properties. */
final class Index extends ResourceObject
{
    public function __construct(
        private readonly ResourceInterface $resource,
        private readonly SiteSetting $setting,
    ) {
    }

    public function onGet(): static
    {
        $nav = $this->resource->get('app://self/nav');
        $this->body = [
            'setting' => $this->setting->name(),
            'nav' => $nav->body,
            'child' => $this->resource->get->uri('app://self/child'),
        ];

        return $this;
    }
}
