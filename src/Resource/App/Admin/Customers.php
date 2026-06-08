<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\CustomerQueryInterface;
use BEAR\EventSourcing\Query\OrderQueryInterface;
use BEAR\Resource\ResourceObject;

class Customers extends ResourceObject
{
    public function __construct(
        private readonly CustomerQueryInterface $customerQuery,
        private readonly OrderQueryInterface $orderQuery,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(
        int|null $id = null,
        string|null $email = null,
        string|null $name = null,
        int|null $customer_status_id = null,
        int $limit = 20,
        int $offset = 0,
    ): static {
        if ($id !== null) {
            $customer = $this->customerQuery->findById($id);
            if ($customer === null) {
                $this->code = 404;
                $this->body = ['error' => 'Customer not found'];

                return $this;
            }

            unset($customer['password'], $customer['salt'], $customer['secret_key'], $customer['reset_key']);
            $customer['orders'] = $this->orderQuery->findByCustomerId($id, 5);
            $this->body = $customer;
        } else {
            $filters = [];
            if ($email !== null) {
                $filters['email'] = $email;
            }

            if ($name !== null) {
                $filters['name'] = $name;
            }

            if ($customer_status_id !== null) {
                $filters['customer_status_id'] = $customer_status_id;
            }

            $customers = $this->customerQuery->findByFilters($filters, $limit, $offset);
            foreach ($customers as &$customer) {
                unset($customer['password'], $customer['salt'], $customer['secret_key'], $customer['reset_key']);
            }

            $total = $this->customerQuery->countByFilters($filters);

            $this->body = [
                'customers' => $customers,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        int|null $customer_status_id = null,
        int|null $point = null,
        string|null $note = null,
    ): static {
        $customer = $this->customerQuery->findById($id);
        if ($customer === null) {
            $this->code = 404;
            $this->body = ['error' => 'Customer not found'];

            return $this;
        }

        $data = [];
        if ($customer_status_id !== null) {
            $data['customer_status_id'] = $customer_status_id;
        }

        if ($point !== null) {
            $data['point'] = $point;
        }

        if ($note !== null) {
            $data['note'] = $note;
        }

        if (! empty($data)) {
            $this->customerQuery->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }
}
