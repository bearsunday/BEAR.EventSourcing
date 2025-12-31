<?php

declare(strict_types=1);

namespace BearEccube\Exception;

use RuntimeException;

class ValidationException extends RuntimeException
{
    private array $errors;

    public function __construct(array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
        $this->errors = $errors;
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
