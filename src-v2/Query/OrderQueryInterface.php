<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Order Query Interface
 *
 * 注文データ取得のインターフェイス。
 * FakeOrderQuery と RealOrderQuery で実装を切り替え可能。
 */
interface OrderQueryInterface
{
    /**
     * 注文一覧を取得
     *
     * @return array{orders: array, total: int, limit: int, offset: int}
     */
    public function findList(
        ?int $customerId = null,
        ?int $statusId = null,
        ?string $orderNo = null,
        int $limit = 20,
        int $offset = 0
    ): array;

    /**
     * IDで注文を取得
     */
    public function findById(int $id): ?array;

    /**
     * 注文番号で取得
     */
    public function findByOrderNo(string $orderNo): ?array;
}
