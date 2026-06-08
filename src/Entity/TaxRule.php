<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\RoundingType;
use DateTimeImmutable;

/**
 * Tax rule entity (税率設定)
 */
class TaxRule extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $productClassId = null;
    protected ProductClass|null $productClass = null;
    protected int|null $productId = null;
    protected Product|null $product = null;
    protected int|null $prefId = null;
    protected int|null $countryId = null;
    protected string $taxRate = '10';
    protected RoundingType|null $roundingType = null;
    protected int $taxAdjust = 0;
    protected DateTimeImmutable|null $applyDate = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getProductClassId(): int|null
    {
        return $this->productClassId;
    }

    public function setProductClassId(int|null $productClassId): static
    {
        $this->productClassId = $productClassId;

        return $this;
    }

    public function getProductClass(): ProductClass|null
    {
        return $this->productClass;
    }

    public function setProductClass(ProductClass|null $productClass): static
    {
        $this->productClass = $productClass;

        return $this;
    }

    public function getProductId(): int|null
    {
        return $this->productId;
    }

    public function setProductId(int|null $productId): static
    {
        $this->productId = $productId;

        return $this;
    }

    public function getProduct(): Product|null
    {
        return $this->product;
    }

    public function setProduct(Product|null $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getPrefId(): int|null
    {
        return $this->prefId;
    }

    public function setPrefId(int|null $prefId): static
    {
        $this->prefId = $prefId;

        return $this;
    }

    public function getCountryId(): int|null
    {
        return $this->countryId;
    }

    public function setCountryId(int|null $countryId): static
    {
        $this->countryId = $countryId;

        return $this;
    }

    public function getTaxRate(): string
    {
        return $this->taxRate;
    }

    public function setTaxRate(string $taxRate): static
    {
        $this->taxRate = $taxRate;

        return $this;
    }

    public function getRoundingType(): RoundingType|null
    {
        return $this->roundingType;
    }

    public function setRoundingType(RoundingType|null $roundingType): static
    {
        $this->roundingType = $roundingType;

        return $this;
    }

    public function getTaxAdjust(): int
    {
        return $this->taxAdjust;
    }

    public function setTaxAdjust(int $taxAdjust): static
    {
        $this->taxAdjust = $taxAdjust;

        return $this;
    }

    public function getApplyDate(): DateTimeImmutable|null
    {
        return $this->applyDate;
    }

    public function setApplyDate(DateTimeImmutable|null $applyDate): static
    {
        $this->applyDate = $applyDate;

        return $this;
    }
}
