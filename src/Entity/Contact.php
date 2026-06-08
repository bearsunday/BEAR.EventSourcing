<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

class Contact extends AbstractEntity
{
    public function __construct(
        public int|null $id = null,
        public int|null $customerId = null,
        public string $name01 = '',
        public string $name02 = '',
        public string|null $kana01 = null,
        public string|null $kana02 = null,
        public string $email = '',
        public string|null $phoneNumber = null,
        public string|null $postalCode = null,
        public int|null $prefId = null,
        public string|null $addr01 = null,
        public string|null $addr02 = null,
        public string $subject = '',
        public string $message = '',
        public int $status = 1, // 1: new, 2: in_progress, 3: closed
        public string|null $response = null,
        public DateTimeImmutable|null $createDate = null,
        public DateTimeImmutable|null $updateDate = null,
    ) {
    }
}
