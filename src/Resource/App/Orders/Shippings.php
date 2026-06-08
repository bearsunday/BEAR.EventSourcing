<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Orders;

use BEAR\EventSourcing\Query\ShippingQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function array_filter;

/**
 * Order shippings resource (配送情報一覧)
 */
class Shippings extends ResourceObject
{
    #[Inject]
    public function __construct(private ShippingQueryInterface $shippingQuery)
    {
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
        string|null $trackingNumber = null,
        string|null $shippingDate = null,
    ): static {
        $data = array_filter([
            'tracking_number' => $trackingNumber,
            'shipping_date' => $shippingDate,
        ], static fn ($v) => $v !== null);

        $this->shippingQuery->update($shippingId, $data);

        $this->body = $this->shippingQuery->findByOrderId($id);

        return $this;
    }
}
