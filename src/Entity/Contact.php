<?php

declare(strict_types=1);

namespace BearEccube\Entity;

class Contact extends AbstractEntity
{
    public function __construct(
        public ?int $id = null,
        public ?int $customerId = null,
        public string $name01 = '',
        public string $name02 = '',
        public ?string $kana01 = null,
        public ?string $kana02 = null,
        public string $email = '',
        public ?string $phoneNumber = null,
        public ?string $postalCode = null,
        public ?int $prefId = null,
        public ?string $addr01 = null,
        public ?string $addr02 = null,
        public string $subject = '',
        public string $message = '',
        public int $status = 1, // 1: new, 2: in_progress, 3: closed
        public ?string $response = null,
        public ?\DateTimeImmutable $createDate = null,
        public ?\DateTimeImmutable $updateDate = null
    ) {}
}
