<?php

declare(strict_types=1);

namespace BearEccube\Module;

use Aura\Sql\ExtendedPdo;
use BEAR\AppMeta\AbstractAppMeta;
use PDO;
use Ray\Di\AbstractModule;

/**
 * Database module
 */
class DbModule extends AbstractModule
{
    public function __construct(
        private readonly AbstractAppMeta $appMeta,
        ?AbstractModule $module = null
    ) {
        parent::__construct($module);
    }

    protected function configure(): void
    {
        $dbConfig = $this->getDbConfig();

        $this->bind(ExtendedPdo::class)->toProvider(PdoProvider::class);
        $this->bind(PDO::class)->toProvider(PdoProvider::class);

        // Bind database configuration
        $this->bind()->annotatedWith('db_dsn')->toInstance($dbConfig['dsn']);
        $this->bind()->annotatedWith('db_user')->toInstance($dbConfig['user']);
        $this->bind()->annotatedWith('db_password')->toInstance($dbConfig['password']);
        $this->bind()->annotatedWith('db_options')->toInstance($dbConfig['options']);
    }

    /**
     * @return array{dsn: string, user: string, password: string, options: array<int, mixed>}
     */
    private function getDbConfig(): array
    {
        // Default configuration - can be overridden by environment variables
        $driver = getenv('DB_DRIVER') ?: 'mysql';
        $host = getenv('DB_HOST') ?: 'localhost';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'eccube';
        $user = getenv('DB_USER') ?: 'root';
        $password = getenv('DB_PASSWORD') ?: '';
        $charset = getenv('DB_CHARSET') ?: 'utf8mb4';

        if ($driver === 'pgsql') {
            $dsn = "pgsql:host={$host};port={$port};dbname={$dbname}";
        } else {
            $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";
        }

        return [
            'dsn' => $dsn,
            'user' => $user,
            'password' => $password,
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ],
        ];
    }
}
