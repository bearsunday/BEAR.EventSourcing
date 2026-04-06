<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\OrderQueryInterface;

/**
 * Fake Order Query
 *
 * FakeJSONを返す。本物の実装の前にResourceとテストを完成させる。
 */
class FakeOrderQuery implements OrderQueryInterface
{
    private string $fakeDir;

    public function __construct(string $fakeDir = '')
    {
        $this->fakeDir = $fakeDir ?: dirname(__DIR__, 3) . '/var/fake/order';
    }

    public function findList(
        ?int $customerId = null,
        ?int $statusId = null,
        ?string $orderNo = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $data = $this->loadJson('list.json');
        $orders = $data['items'] ?? $data['orders'] ?? [];

        // 簡易フィルタリング
        if ($customerId !== null) {
            $orders = array_values(array_filter(
                $orders,
                fn($o) => ($o['customer']['id'] ?? null) === $customerId
            ));
        }

        if ($orderNo !== null) {
            $orders = array_values(array_filter(
                $orders,
                fn($o) => str_contains($o['order_no'] ?? '', $orderNo)
            ));
        }

        return [
            'orders' => $orders,
            'total' => count($orders),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        $data = $this->loadJson('item.json');

        if ($data['id'] !== $id) {
            $list = $this->loadJson('list.json');
            $items = $list['items'] ?? $list['orders'] ?? [];
            foreach ($items as $order) {
                if ($order['id'] === $id) {
                    return $order;
                }
            }
            return null;
        }

        return $data;
    }

    public function findByOrderNo(string $orderNo): ?array
    {
        $list = $this->loadJson('list.json');
        $items = $list['items'] ?? $list['orders'] ?? [];

        foreach ($items as $order) {
            if (($order['order_no'] ?? '') === $orderNo) {
                return $order;
            }
        }

        return null;
    }

    private function loadJson(string $filename): array
    {
        $path = $this->fakeDir . '/' . $filename;
        if (!file_exists($path)) {
            throw new \RuntimeException("Fake JSON not found: {$path}");
        }
        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
