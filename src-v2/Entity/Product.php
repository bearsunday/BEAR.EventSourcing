<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Product
{
    public function __construct(
        public int $id,
        public string $name,
        public ?string $code,
        public ?string $note,
        public ?string $descriptionList,
        public ?string $descriptionDetail,
        public ?string $searchWord,
        public ?string $freeArea,
        public string $createDate,
        public string $updateDate,
        public int $statusId,
    ) {
    }

    public function isPublished(): bool
    {
        return $this->statusId === 1;
    }
}
