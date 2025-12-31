<?php

declare(strict_types=1);

namespace BearEccube\Exception;

use RuntimeException;

class AuthorizationException extends RuntimeException
{
    public function __construct(string $message = 'Access denied')
    {
        parent::__construct($message);
    }
}
