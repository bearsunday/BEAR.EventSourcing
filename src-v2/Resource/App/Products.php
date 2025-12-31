<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\JsonSchema;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\ProductQueryInterface;

/**
 * Products Resource
 *
 * Outside-In: FakeQueryで動作確認済み。
 * 本物のQuery実装に切り替えても同じレスポンス形式。
 */
class Products extends ResourceObject
{
    public function __construct(
        private readonly ProductQueryInterface $productQuery
    ) {}

    /**
     * 商品一覧
     */
    #[JsonSchema('products.get.json')]
    public function onGet(
        ?string $name = null,
        ?int $category_id = null,
        ?int $status_id = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        $this->body = $this->productQuery->findList(
            $name,
            $category_id,
            $status_id,
            $limit,
            $offset
        );

        return $this;
    }
}
