<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\OrderQueryInterface;
use BEAR\EventSourcing\Query\OrderItemQueryInterface;
use BEAR\EventSourcing\Query\ShippingQueryInterface;

class Orders extends ResourceObject
{
    public function __construct(
        private readonly OrderQueryInterface $orderQuery,
        private readonly OrderItemQueryInterface $orderItemQuery,
        private readonly ShippingQueryInterface $shippingQuery
    ) {}

    #[RequireAuth(role: 'admin')]
    public function onGet(
        ?int $id = null,
        ?int $order_status_id = null,
        ?string $order_no = null,
        ?string $customer_email = null,
        ?string $from_date = null,
        ?string $to_date = null,
        int $limit = 20,
        int $offset = 0
    ): static {
        if ($id !== null) {
            $order = $this->orderQuery->findById($id);
            if ($order === null) {
                $this->code = 404;
                $this->body = ['error' => 'Order not found'];
                return $this;
            }
            $order['items'] = $this->orderItemQuery->findByOrderId($id);
            $order['shippings'] = $this->shippingQuery->findByOrderId($id);
            $this->body = $order;
        } else {
            $filters = [];
            if ($order_status_id !== null) $filters['order_status_id'] = $order_status_id;
            if ($order_no !== null) $filters['order_no'] = $order_no;
            if ($customer_email !== null) $filters['customer_email'] = $customer_email;
            if ($from_date !== null) $filters['from_date'] = $from_date;
            if ($to_date !== null) $filters['to_date'] = $to_date;

            $orders = $this->orderQuery->findByFilters($filters, $limit, $offset);
            $total = $this->orderQuery->countByFilters($filters);

            $this->body = [
                'orders' => $orders,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset
            ];
        }
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(int $id, int $order_status_id, ?string $note = null): static
    {
        $order = $this->orderQuery->findById($id);
        if ($order === null) {
            $this->code = 404;
            $this->body = ['error' => 'Order not found'];
            return $this;
        }

        $data = ['order_status_id' => $order_status_id];
        if ($note !== null) $data['note'] = $note;

        $this->orderQuery->update($id, $data);

        $this->code = 200;
        $this->body = ['id' => $id, 'order_status_id' => $order_status_id];
        return $this;
    }
}
