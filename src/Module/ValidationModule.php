<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\Validation\Validator;
use BearEccube\Validation\ValidatorInterface;
use Ray\Di\AbstractModule;

/**
 * Validation module - binds validation interfaces
 */
class ValidationModule extends AbstractModule
{
    protected function configure(): void
    {
        $this->bind(ValidatorInterface::class)->to(Validator::class);
    }
}
