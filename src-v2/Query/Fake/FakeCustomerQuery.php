<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\CustomerQueryInterface;

/**
 * Fake Customer Query
 *
 * FakeJSONを返す。本物の実装の前にResourceとテストを完成させる。
 */
class FakeCustomerQuery implements CustomerQueryInterface
{
    private string $fakeDir;

    public function __construct(string $fakeDir = '')
    {
        $this->fakeDir = $fakeDir ?: dirname(__DIR__, 3) . '/var/fake/customer';
    }

    public function findList(
        ?string $name = null,
        ?string $email = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $data = $this->loadJson('list.json');
        $customers = $data['items'] ?? $data['customers'] ?? [];

        // 簡易フィルタリング
        if ($name !== null) {
            $customers = array_values(array_filter(
                $customers,
                fn($c) => str_contains($c['name01'] ?? '', $name) || str_contains($c['name02'] ?? '', $name)
            ));
        }

        if ($email !== null) {
            $customers = array_values(array_filter(
                $customers,
                fn($c) => str_contains($c['email'] ?? '', $email)
            ));
        }

        return [
            'customers' => $customers,
            'total' => count($customers),
            'limit' => $limit,
            'offset' => $offset,
        ];
    }

    public function findById(int $id): ?array
    {
        $data = $this->loadJson('item.json');

        if ($data['id'] !== $id) {
            $list = $this->loadJson('list.json');
            $items = $list['items'] ?? $list['customers'] ?? [];
            foreach ($items as $customer) {
                if ($customer['id'] === $id) {
                    return $customer;
                }
            }
            return null;
        }

        return $data;
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
