<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Validation;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): array;

    public function isValid(): bool;

    public function getErrors(): array;
}
