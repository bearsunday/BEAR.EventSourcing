<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\DeliveryQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Deliveries resource (配送方法一覧)
 */
class Deliveries extends ResourceObject
{
    #[Inject]
    public function __construct(private DeliveryQueryInterface $deliveryQuery)
    {
    }

    /**
     * Get available delivery methods
     *
     * @param int|null $prefId Prefecture ID to get fees
     */
    public function onGet(int|null $prefId = null): static
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
