<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\ProductStatus;

use function max;
use function min;

/**
 * Product entity (商品)
 */
class Product extends AbstractEntity
{
    protected int|null $id = null;
    protected string $name = '';
    protected string|null $note = null;
    protected string|null $descriptionList = null;
    protected string|null $descriptionDetail = null;
    protected string|null $searchWord = null;
    protected string|null $freeArea = null;
    protected ProductStatus|null $status = null;
    /** @var ProductClass[] */
    protected array $productClasses = [];
    /** @var ProductImage[] */
    protected array $productImages = [];
    /** @var ProductCategory[] */
    protected array $productCategories = [];
    /** @var ProductTag[] */
    protected array $productTags = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string|null $note): static
    {
        $this->note = $note;

        return $this;
    }

    public function getDescriptionList(): string|null
    {
        return $this->descriptionList;
    }

    public function setDescriptionList(string|null $descriptionList): static
    {
        $this->descriptionList = $descriptionList;

        return $this;
    }

    public function getDescriptionDetail(): string|null
    {
        return $this->descriptionDetail;
    }

    public function setDescriptionDetail(string|null $descriptionDetail): static
    {
        $this->descriptionDetail = $descriptionDetail;

        return $this;
    }

    public function getSearchWord(): string|null
    {
        return $this->searchWord;
    }

    public function setSearchWord(string|null $searchWord): static
    {
        $this->searchWord = $searchWord;

        return $this;
    }

    public function getFreeArea(): string|null
    {
        return $this->freeArea;
    }

    public function setFreeArea(string|null $freeArea): static
    {
        $this->freeArea = $freeArea;

        return $this;
    }

    public function getStatus(): ProductStatus|null
    {
        return $this->status;
    }

    public function setStatus(ProductStatus|null $status): static
    {
        $this->status = $status;

        return $this;
    }

    /** @return ProductClass[] */
    public function getProductClasses(): array
    {
        return $this->productClasses;
    }

    /** @param ProductClass[] $productClasses */
    public function setProductClasses(array $productClasses): static
    {
        $this->productClasses = $productClasses;

        return $this;
    }

    public function addProductClass(ProductClass $productClass): static
    {
        $this->productClasses[] = $productClass;

        return $this;
    }

    /** @return ProductImage[] */
    public function getProductImages(): array
    {
        return $this->productImages;
    }

    /** @param ProductImage[] $productImages */
    public function setProductImages(array $productImages): static
    {
        $this->productImages = $productImages;

        return $this;
    }

    public function addProductImage(ProductImage $productImage): static
    {
        $this->productImages[] = $productImage;

        return $this;
    }

    /** @return ProductCategory[] */
    public function getProductCategories(): array
    {
        return $this->productCategories;
    }

    /** @param ProductCategory[] $productCategories */
    public function setProductCategories(array $productCategories): static
    {
        $this->productCategories = $productCategories;

        return $this;
    }

    /** @return ProductTag[] */
    public function getProductTags(): array
    {
        return $this->productTags;
    }

    /** @param ProductTag[] $productTags */
    public function setProductTags(array $productTags): static
    {
        $this->productTags = $productTags;

        return $this;
    }

    /**
     * Get main product image
     */
    public function getMainImage(): ProductImage|null
    {
        foreach ($this->productImages as $image) {
            if ($image->getSortNo() === 0) {
                return $image;
            }
        }

        return $this->productImages[0] ?? null;
    }

    /**
     * Get price range (min - max)
     *
     * @return array{min: ?string, max: ?string}
     */
    public function getPriceRange(): array
    {
        $prices = [];
        foreach ($this->productClasses as $class) {
            if ($class->getPrice02() === null) {
                continue;
            }

            $prices[] = $class->getPrice02();
        }

        if (empty($prices)) {
            return ['min' => null, 'max' => null];
        }

        return [
            'min' => min($prices),
            'max' => max($prices),
        ];
    }

    /**
     * Check if product is in stock
     */
    public function isStock(): bool
    {
        foreach ($this->productClasses as $class) {
            if ($class->isStock()) {
                return true;
            }
        }

        return false;
    }
}
