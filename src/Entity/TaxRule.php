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
    protected ?int $id = null;
    protected ?int $productClassId = null;
    protected ?ProductClass $productClass = null;
    protected ?int $productId = null;
    protected ?Product $product = null;
    protected ?int $prefId = null;
    protected ?int $countryId = null;
    protected string $taxRate = '10';
    protected ?RoundingType $roundingType = null;
    protected int $taxAdjust = 0;
    protected ?DateTimeImmutable $applyDate = null;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getProductClassId(): ?int { return $this->productClassId; }
    public function setProductClassId(?int $productClassId): static { $this->productClassId = $productClassId; return $this; }

    public function getProductClass(): ?ProductClass { return $this->productClass; }
    public function setProductClass(?ProductClass $productClass): static { $this->productClass = $productClass; return $this; }

    public function getProductId(): ?int { return $this->productId; }
    public function setProductId(?int $productId): static { $this->productId = $productId; return $this; }

    public function getProduct(): ?Product { return $this->product; }
    public function setProduct(?Product $product): static { $this->product = $product; return $this; }

    public function getPrefId(): ?int { return $this->prefId; }
    public function setPrefId(?int $prefId): static { $this->prefId = $prefId; return $this; }

    public function getCountryId(): ?int { return $this->countryId; }
    public function setCountryId(?int $countryId): static { $this->countryId = $countryId; return $this; }

    public function getTaxRate(): string { return $this->taxRate; }
    public function setTaxRate(string $taxRate): static { $this->taxRate = $taxRate; return $this; }

    public function getRoundingType(): ?RoundingType { return $this->roundingType; }
    public function setRoundingType(?RoundingType $roundingType): static { $this->roundingType = $roundingType; return $this; }

    public function getTaxAdjust(): int { return $this->taxAdjust; }
    public function setTaxAdjust(int $taxAdjust): static { $this->taxAdjust = $taxAdjust; return $this; }

    public function getApplyDate(): ?DateTimeImmutable { return $this->applyDate; }
    public function setApplyDate(?DateTimeImmutable $applyDate): static { $this->applyDate = $applyDate; return $this; }
}
