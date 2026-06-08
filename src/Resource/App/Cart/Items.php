<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Cart;

use BEAR\EventSourcing\Query\CartItemQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Cart items resource (カート内商品)
 */
class Items extends ResourceObject
{
    #[Inject]
    public function __construct(private CartItemQueryInterface $cartItemQuery)
    {
    }

    /**
     * Get cart items
     *
     * @param string|null $cartKey    Cart key (for guest)
     * @param int|null    $customerId Customer ID (for logged-in user)
     */
    public function onGet(string|null $cartKey = null, int|null $customerId = null): static
    {
        $this->body = $this->cartItemQuery->findByCartKeyOrCustomerId($cartKey, $customerId);

        return $this;
    }

    /**
     * Add item to cart
     *
     * @param int         $productClassId Product class ID
     * @param int         $quantity       Quantity
     * @param string|null $cartKey        Cart key (for guest)
     * @param int|null    $customerId     Customer ID (for logged-in user)
     */
    public function onPost(
        int $productClassId,
        int $quantity = 1,
        string|null $cartKey = null,
        int|null $customerId = null,
    ): static {
        $id = $this->cartItemQuery->addItem($productClassId, $quantity, $cartKey, $customerId);

        $this->code = 201;
        $this->body = ['id' => $id];

        return $this;
    }

    /**
     * Update cart item quantity
     *
     * @param int $id       Cart item ID
     * @param int $quantity New quantity
     */
    public function onPut(int $id, int $quantity): static
    {
        if ($quantity <= 0) {
            $this->cartItemQuery->removeItem($id);
            $this->code = 204;
            $this->body = null;

            return $this;
        }

        $this->cartItemQuery->updateQuantity($id, $quantity);
        $this->body = $this->cartItemQuery->findById($id);

        return $this;
    }

    /**
     * Remove item from cart
     *
     * @param int $id Cart item ID
     */
    public function onDelete(int $id): static
    {
        $this->cartItemQuery->removeItem($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
