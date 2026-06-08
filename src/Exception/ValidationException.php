<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Exception;

use RuntimeException;

class ValidationException extends RuntimeException
{
    public function __construct(private array $errors, string $message = 'Validation failed')
    {
        parent::__construct($message);
    }

    public function getErrors(): array
    {
        return $this->errors;
    }
}
