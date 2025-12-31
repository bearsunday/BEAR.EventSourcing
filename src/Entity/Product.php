<?php

declare(strict_types=1);

namespace BearEccube\Entity;

use BearEccube\Entity\Master\ProductStatus;

/**
 * Product entity (商品)
 */
class Product extends AbstractEntity
{
    protected ?int $id = null;
    protected string $name = '';
    protected ?string $note = null;
    protected ?string $descriptionList = null;
    protected ?string $descriptionDetail = null;
    protected ?string $searchWord = null;
    protected ?string $freeArea = null;
    protected ?ProductStatus $status = null;
    /** @var ProductClass[] */
    protected array $productClasses = [];
    /** @var ProductImage[] */
    protected array $productImages = [];
    /** @var ProductCategory[] */
    protected array $productCategories = [];
    /** @var ProductTag[] */
    protected array $productTags = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    public function getDescriptionList(): ?string
    {
        return $this->descriptionList;
    }

    public function setDescriptionList(?string $descriptionList): static
    {
        $this->descriptionList = $descriptionList;
        return $this;
    }

    public function getDescriptionDetail(): ?string
    {
        return $this->descriptionDetail;
    }

    public function setDescriptionDetail(?string $descriptionDetail): static
    {
        $this->descriptionDetail = $descriptionDetail;
        return $this;
    }

    public function getSearchWord(): ?string
    {
        return $this->searchWord;
    }

    public function setSearchWord(?string $searchWord): static
    {
        $this->searchWord = $searchWord;
        return $this;
    }

    public function getFreeArea(): ?string
    {
        return $this->freeArea;
    }

    public function setFreeArea(?string $freeArea): static
    {
        $this->freeArea = $freeArea;
        return $this;
    }

    public function getStatus(): ?ProductStatus
    {
        return $this->status;
    }

    public function setStatus(?ProductStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    /**
     * @return ProductClass[]
     */
    public function getProductClasses(): array
    {
        return $this->productClasses;
    }

    /**
     * @param ProductClass[] $productClasses
     */
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

    /**
     * @return ProductImage[]
     */
    public function getProductImages(): array
    {
        return $this->productImages;
    }

    /**
     * @param ProductImage[] $productImages
     */
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

    /**
     * @return ProductCategory[]
     */
    public function getProductCategories(): array
    {
        return $this->productCategories;
    }

    /**
     * @param ProductCategory[] $productCategories
     */
    public function setProductCategories(array $productCategories): static
    {
        $this->productCategories = $productCategories;
        return $this;
    }

    /**
     * @return ProductTag[]
     */
    public function getProductTags(): array
    {
        return $this->productTags;
    }

    /**
     * @param ProductTag[] $productTags
     */
    public function setProductTags(array $productTags): static
    {
        $this->productTags = $productTags;
        return $this;
    }

    /**
     * Get main product image
     */
    public function getMainImage(): ?ProductImage
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
            if ($class->getPrice02() !== null) {
                $prices[] = $class->getPrice02();
            }
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
