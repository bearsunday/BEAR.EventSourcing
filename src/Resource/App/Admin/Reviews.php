<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\ReviewQueryInterface;
use BEAR\Resource\ResourceObject;

class Reviews extends ResourceObject
{
    public function __construct(
        private readonly ReviewQueryInterface $query,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(
        int|null $id = null,
        int|null $status_id = null,
        int|null $product_id = null,
        int $limit = 20,
        int $offset = 0,
    ): static {
        if ($id !== null) {
            $review = $this->query->findById($id);
            if ($review === null) {
                $this->code = 404;
                $this->body = ['error' => 'Review not found'];

                return $this;
            }

            $this->body = $review;
        } else {
            $filters = [];
            if ($status_id !== null) {
                $filters['status_id'] = $status_id;
            }

            if ($product_id !== null) {
                $filters['product_id'] = $product_id;
            }

            $reviews = $this->query->findByFilters($filters, $limit, $offset);
            $total = $this->query->countByFilters($filters);

            $this->body = [
                'reviews' => $reviews,
                'total' => $total,
                'limit' => $limit,
                'offset' => $offset,
            ];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(int $id, int $status_id): static
    {
        $review = $this->query->findById($id);
        if ($review === null) {
            $this->code = 404;
            $this->body = ['error' => 'Review not found'];

            return $this;
        }

        $this->query->update($id, ['status_id' => $status_id]);

        $this->code = 200;
        $this->body = ['id' => $id, 'status_id' => $status_id];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $review = $this->query->findById($id);
        if ($review === null) {
            $this->code = 404;
            $this->body = ['error' => 'Review not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
