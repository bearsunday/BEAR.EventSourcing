<?php

declare(strict_types=1);

namespace BearEccube\Entity;

final readonly class Customer
{
    public function __construct(
        public int $id,
        public string $name01,
        public string $name02,
        public ?string $kana01,
        public ?string $kana02,
        public ?string $companyName,
        public string $email,
        public ?string $phoneNumber,
        public ?string $postalCode,
        public ?string $addr01,
        public ?string $addr02,
        public ?string $birth,
        public ?int $buyTimes,
        public ?int $buyTotal,
        public ?int $point,
        public string $createDate,
        public string $updateDate,
        public int $statusId,
    ) {
    }

    public function fullName(): string
    {
        return $this->name01 . ' ' . $this->name02;
    }
}
