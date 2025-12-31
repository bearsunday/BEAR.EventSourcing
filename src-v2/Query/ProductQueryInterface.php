<?php

declare(strict_types=1);

namespace BearEccube\Query;

/**
 * Product Query Interface
 *
 * Outside-In: このインターフェースはFakeJSONの形式から導出された
 */
interface ProductQueryInterface
{
    /**
     * 商品一覧を取得
     *
     * @return array{
     *   products: list<array{
     *     id: int,
     *     name: string,
     *     note: ?string,
     *     description_list: ?string,
     *     status: array{id: int, name: string},
     *     create_date: string,
     *     update_date: string,
     *     classes: list<array{id: int, code: ?string, price02: float, stock: ?int, stock_unlimited: bool}>,
     *     images: list<array{id: int, file_name: string, sort_no: int}>,
     *     categories: list<array{id: int, name: string}>
     *   }>,
     *   total: int,
     *   limit: int,
     *   offset: int
     * }
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
     * @return array{
     *   id: int,
     *   name: string,
     *   note: ?string,
     *   description_list: ?string,
     *   description_detail: ?string,
     *   search_word: ?string,
     *   free_area: ?string,
     *   status: array{id: int, name: string},
     *   create_date: string,
     *   update_date: string,
     *   classes: list<array>,
     *   images: list<array>,
     *   categories: list<array>,
     *   tags: list<array>
     * }|null
     */
    public function findById(int $id): ?array;
}
