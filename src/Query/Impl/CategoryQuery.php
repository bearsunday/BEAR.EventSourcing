<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\CategoryQueryInterface;
use DateTimeImmutable;

/**
 * Category query implementation
 */
class CategoryQuery implements CategoryQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {
    }

    /**
     * @inheritDoc
     */
    public function findByParentId(?int $parentId = null): array
    {
        if ($parentId === null) {
            $sql = 'SELECT * FROM category WHERE parent_id IS NULL ORDER BY sort_no';
            return $this->pdo->fetchAll($sql);
        }

        $sql = 'SELECT * FROM category WHERE parent_id = :parent_id ORDER BY sort_no';
        return $this->pdo->fetchAll($sql, ['parent_id' => $parentId]);
    }

    /**
     * @inheritDoc
     */
    public function getTree(): array
    {
        $sql = 'SELECT * FROM category ORDER BY sort_no';
        $categories = $this->pdo->fetchAll($sql);

        return $this->buildTree($categories);
    }

    /**
     * @inheritDoc
     */
    public function findById(int $id): ?array
    {
        $sql = 'SELECT * FROM category WHERE id = :id';
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

        // Calculate level
        if (isset($data['parent_id'])) {
            $parent = $this->findById($data['parent_id']);
            $data['level'] = ($parent['level'] ?? 0) + 1;
        } else {
            $data['level'] = 1;
        }

        $columns = implode(', ', array_keys($data));
        $placeholders = ':' . implode(', :', array_keys($data));

        $sql = "INSERT INTO category ({$columns}) VALUES ({$placeholders})";
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

        $sql = 'UPDATE category SET ' . implode(', ', $sets) . ' WHERE id = :id';
        $this->pdo->perform($sql, $data);
    }

    /**
     * @inheritDoc
     */
    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM category WHERE id = :id', ['id' => $id]);
    }

    /**
     * @inheritDoc
     */
    public function getPath(int $id): array
    {
        $path = [];
        $current = $this->findById($id);

        while ($current !== null) {
            array_unshift($path, $current);
            if ($current['parent_id'] === null) {
                break;
            }
            $current = $this->findById($current['parent_id']);
        }

        return $path;
    }

    /**
     * Build tree structure from flat array
     *
     * @param array<int, array<string, mixed>> $categories
     * @return array<int, array<string, mixed>>
     */
    private function buildTree(array $categories, ?int $parentId = null): array
    {
        $tree = [];

        foreach ($categories as $category) {
            if ($category['parent_id'] === $parentId) {
                $children = $this->buildTree($categories, $category['id']);
                if (!empty($children)) {
                    $category['children'] = $children;
                }
                $tree[] = $category;
            }
        }

        return $tree;
    }
}
