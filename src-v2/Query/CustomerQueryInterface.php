<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Customer Query Interface
 *
 * 顧客データ取得のインターフェイス。
 * FakeCustomerQuery と RealCustomerQuery で実装を切り替え可能。
 */
interface CustomerQueryInterface
{
    /**
     * 顧客一覧を取得
     *
     * @return array{customers: array, total: int, limit: int, offset: int}
     */
    public function findList(
        ?string $name = null,
        ?string $email = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * IDで顧客を取得
     */
    public function findById(int $id): ?array;
}
