<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Sql;

use RuntimeException;

use function file_get_contents;
use function is_file;
use function is_string;
use function sprintf;
use function trim;

final readonly class SqlQuery
{
    public function __construct(
        private string $sqlDir = __DIR__ . '/../../sql',
    ) {
    }

    public function get(string $queryName): string
    {
        $file = sprintf('%s/%s.sql', $this->sqlDir, $queryName);
        if (! is_file($file)) {
            throw new RuntimeException(sprintf('SQL file not found: %s', $file));
        }

        $sql = file_get_contents($file);
        if (! is_string($sql)) {
            throw new RuntimeException(sprintf('SQL file is not readable: %s', $file));
        }

        return trim($sql);
    }
}
