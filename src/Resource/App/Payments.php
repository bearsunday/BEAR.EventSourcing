<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\PaymentQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function array_filter;
use function array_values;
use function bccomp;

/**
 * Payments resource (支払方法一覧)
 */
class Payments extends ResourceObject
{
    #[Inject]
    public function __construct(private PaymentQueryInterface $paymentQuery)
    {
    }

    /**
     * Get available payment methods
     *
     * @param int|null    $deliveryId Filter by delivery method
     * @param string|null $amount     Order amount to check rules
     */
    public function onGet(int|null $deliveryId = null, string|null $amount = null): static
    {
        if ($deliveryId !== null) {
            $payments = $this->paymentQuery->findByDeliveryId($deliveryId);
        } else {
            $payments = $this->paymentQuery->findAll();
        }

        // Filter by amount rules if provided
        if ($amount !== null) {
            $payments = array_filter($payments, static function ($payment) use ($amount) {
                if ($payment['rule_min'] && bccomp($amount, $payment['rule_min']) < 0) {
                    return false;
                }

                return ! $payment['rule_max'] || bccomp($amount, $payment['rule_max']) <= 0;
            });
        }

        $this->body = array_values($payments);

        return $this;
    }
}
