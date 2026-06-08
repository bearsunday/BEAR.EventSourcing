<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

interface TaxServiceInterface
{
    /**
     * Calculate tax for a given price
     *
     * @return array{price: string, tax_rate: string, tax: string, price_inc_tax: string}
     */
    public function calculateTax(
        string $price,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): array;

    /**
     * Get price including tax
     */
    public function getTaxIncludedPrice(
        string $price,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): string;

    /**
     * Get price excluding tax from tax-included price
     */
    public function getTaxExcludedPrice(
        string $priceIncTax,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): string;
}
