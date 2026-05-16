<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\MemberQueryInterface;

class Members extends ResourceObject
{
    public function __construct(
        private readonly MemberQueryInterface $query
    ) {
    }

    #[Link(rel: 'item', href: '/member{?id}')]
    #[JsonSchema('members.get.json')]
    public function onGet(?string $name = null, int $limit = 20, int $offset = 0): static
    {
        $this->body = $this->query->findList($name, $limit, $offset);
        return $this;
    }
}
