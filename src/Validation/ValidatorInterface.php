<?php

declare(strict_types=1);

namespace BearEccube\Validation;

interface ValidatorInterface
{
    public function validate(array $data, array $rules): array;

    public function isValid(): bool;

    public function getErrors(): array;
}
