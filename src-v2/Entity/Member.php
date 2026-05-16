<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Member
{
    public function __construct(
        public int $id,
        public string $name,
        public string $loginId,
        public ?string $department,
        public ?int $sortNo,
        public bool $twoFactorAuthEnabled,
        public string $createDate,
        public string $updateDate,
        public ?string $loginDate,
        public int $authorityId,
    ) {
    }

    public function hasTwoFactorAuth(): bool
    {
        return $this->twoFactorAuthEnabled;
    }
}
