<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Module;

use Aura\Sql\ExtendedPdo;
use Ray\Di\Di\Named;
use Ray\Di\ProviderInterface;

/**
 * PDO provider
 */
class PdoProvider implements ProviderInterface
{
    public function __construct(
        #[Named('db_dsn')]
        private readonly string $dsn,
        #[Named('db_user')]
        private readonly string $user,
        #[Named('db_password')]
        private readonly string $password,
        #[Named('db_options')]
        private readonly array $options,
    ) {
    }

    public function get(): ExtendedPdo
    {
        return new ExtendedPdo(
            $this->dsn,
            $this->user,
            $this->password,
            $this->options
        );
    }
}
