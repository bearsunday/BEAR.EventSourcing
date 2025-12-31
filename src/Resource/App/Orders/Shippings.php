<?php

declare(strict_types=1);

namespace BearEccube\Resource\App\Orders;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\ShippingQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Order shippings resource (配送情報一覧)
 */
class Shippings extends ResourceObject
{
    private ShippingQueryInterface $shippingQuery;

    #[Inject]
    public function __construct(ShippingQueryInterface $shippingQuery)
    {
        $this->shippingQuery = $shippingQuery;
    }

    /**
     * Get shippings by order ID
     *
     * @param int $id Order ID
     */
    public function onGet(int $id): static
    {
        $this->body = $this->shippingQuery->findByOrderId($id);
        return $this;
    }

    /**
     * Update shipping info
     *
     * @param int         $id             Order ID
     * @param int         $shippingId     Shipping ID
     * @param string|null $trackingNumber Tracking number
     * @param string|null $shippingDate   Shipping date (Y-m-d)
     */
    public function onPut(
        int $id,
        int $shippingId,
        ?string $trackingNumber = null,
        ?string $shippingDate = null
    ): static {
        $data = array_filter([
            'tracking_number' => $trackingNumber,
            'shipping_date' => $shippingDate,
        ], fn($v) => $v !== null);

        $this->shippingQuery->update($shippingId, $data);

        $this->body = $this->shippingQuery->findByOrderId($id);

        return $this;
    }
}
