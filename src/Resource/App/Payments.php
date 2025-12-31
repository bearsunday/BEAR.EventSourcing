<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\PaymentQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Payments resource (支払方法一覧)
 */
class Payments extends ResourceObject
{
    private PaymentQueryInterface $paymentQuery;

    #[Inject]
    public function __construct(PaymentQueryInterface $paymentQuery)
    {
        $this->paymentQuery = $paymentQuery;
    }

    /**
     * Get available payment methods
     *
     * @param int|null    $deliveryId Filter by delivery method
     * @param string|null $amount     Order amount to check rules
     */
    public function onGet(?int $deliveryId = null, ?string $amount = null): static
    {
        if ($deliveryId !== null) {
            $payments = $this->paymentQuery->findByDeliveryId($deliveryId);
        } else {
            $payments = $this->paymentQuery->findAll();
        }

        // Filter by amount rules if provided
        if ($amount !== null) {
            $payments = array_filter($payments, function ($payment) use ($amount) {
                if ($payment['rule_min'] && bccomp($amount, $payment['rule_min']) < 0) {
                    return false;
                }
                if ($payment['rule_max'] && bccomp($amount, $payment['rule_max']) > 0) {
                    return false;
                }
                return true;
            });
        }

        $this->body = array_values($payments);
        return $this;
    }
}
