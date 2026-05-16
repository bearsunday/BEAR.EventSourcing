<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\MemberQueryInterface;

class FakeMemberQuery extends AbstractFakeQuery implements MemberQueryInterface
{
    protected function fakeName(): string
    {
        return 'member';
    }

    public function findList(?string $name = null, int $limit = 20, int $offset = 0): array
    {
        $members = $this->loadItems();

        if ($name !== null) {
            $members = array_values(array_filter(
                $members,
                static fn($m) => str_contains($m['name'] ?? '', $name)
            ));
        }

        return [
            'members' => $members,
            'total' => count($members),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        return $this->findItemById($id);
    }

    public function findByLoginId(string $loginId): ?array
    {
        foreach ($this->loadItems() as $member) {
            if (($member['login_id'] ?? '') === $loginId) {
                return $member;
            }
        }
        return null;
    }
}
