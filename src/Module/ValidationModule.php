<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use BEAR\EventSourcing\Validation\Validator;
use BEAR\EventSourcing\Validation\ValidatorInterface;
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
