<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\Code;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\MemberQueryInterface;

class Member extends ResourceObject
{
    public function __construct(
        private readonly MemberQueryInterface $query
    ) {
    }

    #[Link(rel: 'collection', href: '/members')]
    #[JsonSchema('member.get.json')]
    public function onGet(int $id): static
    {
        $member = $this->query->findById($id);

        if ($member === null) {
            $this->code = Code::NOT_FOUND;
            $this->body = ['error' => 'Member not found'];
            return $this;
        }

        $this->body = $member;
        return $this;
    }
}
