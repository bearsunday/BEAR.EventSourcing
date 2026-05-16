<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\ProductQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Product resource (商品詳細)
 *
 * @Link(rel="categories", href="/products/{id}/categories")
 * @Link(rel="classes", href="/products/{id}/classes")
 * @Link(rel="images", href="/products/{id}/images")
 */
class Product extends ResourceObject
{
    private ProductQueryInterface $productQuery;

    #[Inject]
    public function __construct(ProductQueryInterface $productQuery)
    {
        $this->productQuery = $productQuery;
    }

    /**
     * Get product by ID
     *
     * @param int $id Product ID
     *
     * @Embed(rel="categories", src="/products/{id}/categories")
     * @Embed(rel="classes", src="/products/{id}/classes")
     * @Embed(rel="images", src="/products/{id}/images")
     */
    public function onGet(int $id): static
    {
        $product = $this->productQuery->findById($id);

        if ($product === null) {
            $this->code = 404;
            $this->body = ['error' => 'Product not found'];
            return $this;
        }

        $this->body = $product;

        return $this;
    }

    /**
     * Update product
     *
     * @param int         $id                Product ID
     * @param string|null $name              Product name
     * @param int|null    $status            Product status ID
     * @param string|null $descriptionList   Short description
     * @param string|null $descriptionDetail Full description
     * @param string|null $searchWord        Search keywords
     * @param string|null $note              Admin note
     */
    public function onPut(
        int $id,
        ?string $name = null,
        ?int $status = null,
        ?string $descriptionList = null,
        ?string $descriptionDetail = null,
        ?string $searchWord = null,
        ?string $note = null
    ): static {
        $product = $this->productQuery->findById($id);

        if ($product === null) {
            $this->code = 404;
            $this->body = ['error' => 'Product not found'];
            return $this;
        }

        $data = array_filter([
            'name' => $name,
            'status_id' => $status,
            'description_list' => $descriptionList,
            'description_detail' => $descriptionDetail,
            'search_word' => $searchWord,
            'note' => $note,
        ], fn($v) => $v !== null);

        $this->productQuery->update($id, $data);

        $this->body = $this->productQuery->findById($id);

        return $this;
    }

    /**
     * Delete product
     *
     * @param int $id Product ID
     */
    public function onDelete(int $id): static
    {
        $product = $this->productQuery->findById($id);

        if ($product === null) {
            $this->code = 404;
            $this->body = ['error' => 'Product not found'];
            return $this;
        }

        $this->productQuery->delete($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
