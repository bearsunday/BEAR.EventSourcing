<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\PluginQueryInterface;
use DateTimeImmutable;

class PluginQuery implements PluginQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {}

    public function findById(int $id): ?array
    {
        $result = $this->pdo->fetchOne('SELECT * FROM plugin WHERE id = :id', ['id' => $id]);
        return $result ?: null;
    }

    public function findByCode(string $code): ?array
    {
        $result = $this->pdo->fetchOne('SELECT * FROM plugin WHERE code = :code', ['code' => $code]);
        return $result ?: null;
    }

    public function findAll(bool $enabledOnly = false): array
    {
        $sql = 'SELECT * FROM plugin';
        if ($enabledOnly) {
            $sql .= ' WHERE enabled = 1';
        }
        $sql .= ' ORDER BY sort_no ASC';
        return $this->pdo->fetchAll($sql);
    }

    public function install(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO plugin (name, code, version, source, enabled, sort_no, create_date, update_date)
             VALUES (:name, :code, :version, :source, :enabled, :sort_no, :create_date, :update_date)',
            array_merge($data, [
                'enabled' => 0,
                'sort_no' => $data['sort_no'] ?? 0,
                'create_date' => $now,
                'update_date' => $now,
            ])
        );
        return (int)$this->pdo->lastInsertId();
    }

    public function enable(int $id): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'UPDATE plugin SET enabled = 1, update_date = :update_date WHERE id = :id',
            ['id' => $id, 'update_date' => $now]
        );
    }

    public function disable(int $id): void
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'UPDATE plugin SET enabled = 0, update_date = :update_date WHERE id = :id',
            ['id' => $id, 'update_date' => $now]
        );
    }

    public function uninstall(int $id): void
    {
        $this->pdo->perform('DELETE FROM plugin WHERE id = :id', ['id' => $id]);
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE plugin SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }
}
