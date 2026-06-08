<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Service;

use BEAR\EventSourcing\Entity\Master\RoundingType;
use BEAR\EventSourcing\Query\TaxRuleQueryInterface;

use function bcadd;
use function bccomp;
use function bcdiv;
use function bcmul;
use function bcsub;

/**
 * Tax calculation service
 */
class TaxService implements TaxServiceInterface
{
    public function __construct(
        private readonly TaxRuleQueryInterface $taxRuleQuery,
    ) {
    }

    /** @inheritDoc */
    public function calculateTax(
        string $price,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): array {
        $taxRule = $this->taxRuleQuery->findApplicable($productClassId, $prefId);

        if ($taxRule === null) {
            // Default 10% tax
            $taxRule = [
                'tax_rate' => '10',
                'rounding_type_id' => RoundingType::ROUND,
            ];
        }

        $taxRate = $taxRule['tax_rate'];
        $roundingType = $taxRule['rounding_type_id'] ?? RoundingType::ROUND;

        // Calculate tax
        $taxAmount = bcmul($price, bcdiv($taxRate, '100', 10), 10);

        // Apply rounding
        $taxAmount = $this->round($taxAmount, $roundingType);

        return [
            'price' => $price,
            'tax_rate' => $taxRate,
            'tax' => $taxAmount,
            'price_inc_tax' => bcadd($price, $taxAmount, 0),
        ];
    }

    /** @inheritDoc */
    public function getTaxIncludedPrice(
        string $price,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): string {
        $result = $this->calculateTax($price, $productClassId, $prefId);

        return $result['price_inc_tax'];
    }

    /** @inheritDoc */
    public function getTaxExcludedPrice(
        string $priceIncTax,
        int|null $productClassId = null,
        int|null $prefId = null,
    ): string {
        $taxRule = $this->taxRuleQuery->findApplicable($productClassId, $prefId);
        $taxRate = $taxRule['tax_rate'] ?? '10';

        // price = priceIncTax / (1 + taxRate/100)
        $divisor = bcadd('1', bcdiv($taxRate, '100', 10), 10);

        return bcdiv($priceIncTax, $divisor, 0);
    }

    private function round(string $value, int $roundingType): string
    {
        return match ($roundingType) {
            RoundingType::FLOOR => bcadd($value, '0', 0), // truncate
            RoundingType::CEIL => bccomp(bcsub($value, bcadd($value, '0', 0), 10), '0') > 0
                ? bcadd(bcadd($value, '0', 0), '1', 0)
                : bcadd($value, '0', 0),
            default => bcadd($value, '0.5', 0), // round
        };
    }
}
