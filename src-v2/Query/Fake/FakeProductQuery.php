<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use BearEccube\Query\ProductQueryInterface;

/**
 * Fake Product Query
 *
 * FakeJSONを返す。本物の実装の前にResourceとテストを完成させる。
 */
class FakeProductQuery implements ProductQueryInterface
{
    private string $fakeDir;

    public function __construct(string $fakeDir = '')
    {
        $this->fakeDir = $fakeDir ?: dirname(__DIR__, 3) . '/var/fake/product';
    }

    public function findList(
        ?string $name = null,
        ?int $categoryId = null,
        ?int $statusId = null,
        int $limit = 20,
        int $offset = 0
    ): array {
        $data = $this->loadJson('list.json');

        // 簡易フィルタリング（Fakeなので完全な実装は不要）
        if ($name !== null) {
            $data['products'] = array_values(array_filter(
                $data['products'],
                fn($p) => str_contains($p['name'], $name)
            ));
            $data['total'] = count($data['products']);
        }

        $data['limit'] = $limit;
        $data['offset'] = $offset;

        return $data;
    }

    public function findById(int $id): ?array
    {
        $data = $this->loadJson('item.json');

        // Fakeなので固定IDを返す（本物は実際にDBから取得）
        if ($data['id'] !== $id) {
            // 別のIDの場合はlistから探す
            $list = $this->loadJson('list.json');
            foreach ($list['products'] as $product) {
                if ($product['id'] === $id) {
                    return $product;
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
