<?php

declare(strict_types=1);

namespace BearEccube\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BearEccube\Query\TaxRuleQueryInterface;
use DateTimeImmutable;

class TaxRuleQuery implements TaxRuleQueryInterface
{
    public function __construct(private readonly ExtendedPdo $pdo) {}

    public function findApplicable(?int $productClassId = null, ?int $prefId = null): ?array
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        // Try most specific first (product class + pref)
        if ($productClassId !== null && $prefId !== null) {
            $result = $this->pdo->fetchOne(
                'SELECT * FROM tax_rule WHERE product_class_id = :product_class_id AND pref_id = :pref_id AND apply_date <= :now ORDER BY apply_date DESC LIMIT 1',
                ['product_class_id' => $productClassId, 'pref_id' => $prefId, 'now' => $now]
            );
            if ($result) return $result;
        }

        // Try product class only
        if ($productClassId !== null) {
            $result = $this->pdo->fetchOne(
                'SELECT * FROM tax_rule WHERE product_class_id = :product_class_id AND pref_id IS NULL AND apply_date <= :now ORDER BY apply_date DESC LIMIT 1',
                ['product_class_id' => $productClassId, 'now' => $now]
            );
            if ($result) return $result;
        }

        // Try pref only
        if ($prefId !== null) {
            $result = $this->pdo->fetchOne(
                'SELECT * FROM tax_rule WHERE product_class_id IS NULL AND pref_id = :pref_id AND apply_date <= :now ORDER BY apply_date DESC LIMIT 1',
                ['pref_id' => $prefId, 'now' => $now]
            );
            if ($result) return $result;
        }

        // Fall back to default rule
        return $this->pdo->fetchOne(
            'SELECT * FROM tax_rule WHERE product_class_id IS NULL AND pref_id IS NULL AND apply_date <= :now ORDER BY apply_date DESC LIMIT 1',
            ['now' => $now]
        ) ?: null;
    }

    public function findAll(): array
    {
        return $this->pdo->fetchAll('SELECT * FROM tax_rule ORDER BY apply_date DESC');
    }

    public function findById(int $id): ?array
    {
        return $this->pdo->fetchOne('SELECT * FROM tax_rule WHERE id = :id', ['id' => $id]) ?: null;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;
        $cols = implode(', ', array_keys($data));
        $ph = ':' . implode(', :', array_keys($data));
        $this->pdo->perform("INSERT INTO tax_rule ({$cols}) VALUES ({$ph})", $data);
        return (int)$this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE tax_rule SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM tax_rule WHERE id = :id', ['id' => $id]);
    }
}
