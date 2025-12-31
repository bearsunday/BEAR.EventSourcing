<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\DeliveryQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Deliveries resource (配送方法一覧)
 */
class Deliveries extends ResourceObject
{
    private DeliveryQueryInterface $deliveryQuery;

    #[Inject]
    public function __construct(DeliveryQueryInterface $deliveryQuery)
    {
        $this->deliveryQuery = $deliveryQuery;
    }

    /**
     * Get available delivery methods
     *
     * @param int|null $prefId Prefecture ID to get fees
     */
    public function onGet(?int $prefId = null): static
    {
        $deliveries = $this->deliveryQuery->findAll();

        if ($prefId !== null) {
            // Attach delivery fees for the prefecture
            foreach ($deliveries as &$delivery) {
                $delivery['fee'] = $this->deliveryQuery->getDeliveryFee($delivery['id'], $prefId);
            }
        }

        $this->body = $deliveries;
        return $this;
    }
}
