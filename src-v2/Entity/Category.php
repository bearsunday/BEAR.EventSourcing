<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Category
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $categoryName,
        public ?int $hierarchy,
        public ?int $sortNo,
        public string $createDate,
        public string $updateDate,
        public ?int $parentId,
    ) {
    }

    public function isRoot(): bool
    {
        return $this->parentId === null;
    }
}
