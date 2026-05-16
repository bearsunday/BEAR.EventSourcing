<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\FavoriteQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Customer favorites resource (お気に入り一覧)
 */
#[RequireAuth(type: 'customer')]
class Favorites extends ResourceObject
{
    private FavoriteQueryInterface $favoriteQuery;
    private ?array $authUser = null;

    #[Inject]
    public function __construct(FavoriteQueryInterface $favoriteQuery)
    {
        $this->favoriteQuery = $favoriteQuery;
    }

    public function setAuthUser(array $user): void
    {
        $this->authUser = $user;
    }

    /**
     * Get customer's favorite products
     *
     * @param int $page  Page number
     * @param int $limit Items per page
     */
    public function onGet(int $page = 1, int $limit = 20): static
    {
        $customerId = $this->authUser['id'] ?? 0;
        $offset = ($page - 1) * $limit;

        $this->body = [
            'favorites' => $this->favoriteQuery->findByCustomerId($customerId, $limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->favoriteQuery->countByCustomerId($customerId),
            ],
        ];

        return $this;
    }

    /**
     * Add product to favorites
     *
     * @param int $productId Product ID
     */
    public function onPost(int $productId): static
    {
        $customerId = $this->authUser['id'] ?? 0;

        if ($this->favoriteQuery->exists($customerId, $productId)) {
            $this->code = 409;
            $this->body = ['error' => 'Product already in favorites'];
            return $this;
        }

        $id = $this->favoriteQuery->add($customerId, $productId);

        $this->code = 201;
        $this->body = ['id' => $id, 'product_id' => $productId];

        return $this;
    }

    /**
     * Remove product from favorites
     *
     * @param int $productId Product ID
     */
    public function onDelete(int $productId): static
    {
        $customerId = $this->authUser['id'] ?? 0;

        $this->favoriteQuery->remove($customerId, $productId);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
