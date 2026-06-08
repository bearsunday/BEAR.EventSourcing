<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\OrderQueryInterface;
use BEAR\Resource\Annotation\Embed;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function array_filter;

/**
 * Order resource (注文詳細)
 *
 * @Link(rel="items", href="/orders/{id}/items")
 * @Link(rel="shippings", href="/orders/{id}/shippings")
 * @Link(rel="customer", href="/customers/{customer_id}")
 */
class Order extends ResourceObject
{
    #[Inject]
    public function __construct(private OrderQueryInterface $orderQuery)
    {
    }

    /**
     * Get order by ID
     *
     * @param int $id Order ID
     *
     * @Embed(rel="items", src="/orders/{id}/items")
     * @Embed(rel="shippings", src="/orders/{id}/shippings")
     */
    public function onGet(int $id): static
    {
        $order = $this->orderQuery->findById($id);

        if ($order === null) {
            $this->code = 404;
            $this->body = ['error' => 'Order not found'];

            return $this;
        }

        $this->body = $order;

        return $this;
    }

    /**
     * Update order status
     *
     * @param int         $id     Order ID
     * @param int|null    $status Order status ID
     * @param string|null $note   Admin note
     */
    public function onPut(int $id, int|null $status = null, string|null $note = null): static
    {
        $order = $this->orderQuery->findById($id);

        if ($order === null) {
            $this->code = 404;
            $this->body = ['error' => 'Order not found'];

            return $this;
        }

        $data = array_filter([
            'order_status_id' => $status,
            'note' => $note,
        ], static fn ($v) => $v !== null);

        $this->orderQuery->update($id, $data);

        $this->body = $this->orderQuery->findById($id);

        return $this;
    }

    /**
     * Cancel order
     *
     * @param int $id Order ID
     */
    public function onDelete(int $id): static
    {
        $order = $this->orderQuery->findById($id);

        if ($order === null) {
            $this->code = 404;
            $this->body = ['error' => 'Order not found'];

            return $this;
        }

        // Soft delete by changing status to cancelled
        $this->orderQuery->cancel($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
