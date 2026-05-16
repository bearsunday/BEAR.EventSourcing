<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Product Query Interface
 *
 * Outside-In: このインターフェースは var/schema/products.get.json から導出された。
 * レスポンス構造は JsonSchema が唯一の真実。docblock は補助情報。
 */
interface ProductQueryInterface
{
    /**
     * 商品一覧を取得
     *
     * @return array{products: list<array<string, mixed>>, total: int, limit: int, offset: int}
     */
    public function findList(
        ?string $name = null,
        ?int $categoryId = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * 商品詳細を取得
     *
     * @return array<string, mixed>|null
     */
    public function findById(int $id): ?array;
}
