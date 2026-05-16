<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\CartQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Cart resource (カート)
 *
 * @Link(rel="items", href="/cart/items")
 * @Link(rel="checkout", href="/cart/checkout")
 */
class Cart extends ResourceObject
{
    private CartQueryInterface $cartQuery;

    #[Inject]
    public function __construct(CartQueryInterface $cartQuery)
    {
        $this->cartQuery = $cartQuery;
    }

    /**
     * Get cart by cart key or customer ID
     *
     * @param string|null $cartKey    Cart key (for guest)
     * @param int|null    $customerId Customer ID (for logged-in user)
     */
    public function onGet(?string $cartKey = null, ?int $customerId = null): static
    {
        $cart = $this->cartQuery->findByKeyOrCustomerId($cartKey, $customerId);

        if ($cart === null) {
            $this->body = [
                'items' => [],
                'total_quantity' => 0,
                'total_price' => '0',
            ];
            return $this;
        }

        $this->body = $cart;

        return $this;
    }

    /**
     * Create or update cart
     *
     * @param string|null $cartKey    Cart key (for guest)
     * @param int|null    $customerId Customer ID (for logged-in user)
     */
    public function onPost(?string $cartKey = null, ?int $customerId = null): static
    {
        $id = $this->cartQuery->createOrGet($cartKey, $customerId);

        $this->code = 201;
        $this->body = ['id' => $id];

        return $this;
    }

    /**
     * Clear cart
     *
     * @param string|null $cartKey    Cart key (for guest)
     * @param int|null    $customerId Customer ID (for logged-in user)
     */
    public function onDelete(?string $cartKey = null, ?int $customerId = null): static
    {
        $this->cartQuery->clear($cartKey, $customerId);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
