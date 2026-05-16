<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\ProductQueryInterface;
use DateTimeImmutable;

/**
 * Product query implementation
 */
class ProductQuery implements ProductQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {
    }

    /**
     * @inheritDoc
     */
    public function findAll(
        ?int $categoryId = null,
        ?string $name = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $sql = 'SELECT p.*, ps.name AS status_name FROM product p
                LEFT JOIN mtb_product_status ps ON p.product_status_id = ps.id';
        $params = [];
        $where = [];

        if ($categoryId !== null) {
            $sql .= ' INNER JOIN product_category pc ON pc.product_id = p.id';
            $where[] = 'pc.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($name !== null) {
            $where[] = 'p.name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        $sql .= ' ORDER BY p.update_date DESC LIMIT :limit OFFSET :offset';
        $params['limit'] = $limit;
        $params['offset'] = $offset;

        return $this->pdo->fetchAll($sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function count(?int $categoryId = null, ?string $name = null): int
    {
        $sql = 'SELECT COUNT(DISTINCT p.id) FROM product p';
        $params = [];
        $where = [];

        if ($categoryId !== null) {
            $sql .= ' INNER JOIN product_category pc ON pc.product_id = p.id';
            $where[] = 'pc.category_id = :category_id';
            $params['category_id'] = $categoryId;
        }

        if ($name !== null) {
            $where[] = 'p.name LIKE :name';
            $params['name'] = '%' . $name . '%';
        }

        if (!empty($where)) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }

        return (int)$this->pdo->fetchValue($sql, $params);
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT p.*, ps.name AS status_name FROM product p
                LEFT JOIN mtb_product_status ps ON p.product_status_id = ps.id
                WHERE p.id = :id';

        $result = $this->pdo->fetchOne($sql, ['id' => $id]);
        return $result ?: null;
    }

    /**
     * @inheritDoc
     */
    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $data['create_date'] = $now;
        $data['update_date'] = $now;

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO product ({$columns}) VALUES ({$placeholders})";
        $this->pdo->perform($sql, $data);

        return (int)$this->pdo->lastInsertId();
    }

    /**
     * @inheritDoc
     */
    public function update(int $id, array $data): void
    {
        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');

        $sets = [];
        foreach (array_keys($data) as $key) {
            $sets[] = "{$key} = :{$key}";
        }
        $data['id'] = $id;

        $sql = 'UPDATE product SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->pdo->perform($sql, $data);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM product WHERE id = :id', ['id' => $id]);
    }
}
