<?php

declare(strict_types=1);

namespace BearEccube\Query\Fake;

use RuntimeException;

/**
 * FakeQuery の共通基盤。
 *
 * var/fake/{entity}/{list|item}.json を読み込んで返す。
 * サブクラスは findList / findById で具体的なフィルタを実装するだけでよい。
 */
abstract class AbstractFakeQuery
{
    protected readonly string $fakeDir;

    /** 例: 'product', 'customer', 'order' */
    abstract protected function fakeName(): string;

    public function __construct(?string $fakeDir = null)
    {
        $this->fakeDir = $fakeDir ?? dirname(__DIR__, 3) . '/var/fake/' . $this->fakeName();
    }

    /** list.json の "items" を取り出す */
    protected function loadItems(): array
    {
        $data = $this->loadJson('list.json');
        return $data['items'] ?? [];
    }

    /** id 一致で list から1件取得。Fake 用の素朴な実装 */
    protected function findItemById(int $id): ?array
    {
        $item = $this->loadJson('item.json');
        if ($item['id'] === $id) {
            return $item;
        }
        foreach ($this->loadItems() as $row) {
            if (($row['id'] ?? null) === $id) {
                return $row;
            }
        }
        return null;
    }

    private function loadJson(string $filename): array
    {
        $path = $this->fakeDir . '/' . $filename;
        if (!file_exists($path)) {
            throw new RuntimeException("Fake JSON not found: {$path}");
        }
        return json_decode(file_get_contents($path), true, 512, JSON_THROW_ON_ERROR);
    }
}
