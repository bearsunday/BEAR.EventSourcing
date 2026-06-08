<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Cart;

use BEAR\EventSourcing\Query\CartQueryInterface;
use BEAR\EventSourcing\Query\OrderQueryInterface;
use BEAR\EventSourcing\Service\CheckoutServiceInterface;
use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;
use Throwable;

/**
 * Checkout resource (注文確定)
 *
 * @Link(rel="order", href="/orders/{id}")
 */
class Checkout extends ResourceObject
{
    #[Inject]
    public function __construct(
        private CartQueryInterface $cartQuery,
        private OrderQueryInterface $orderQuery,
        private CheckoutServiceInterface $checkoutService,
    ) {
    }

    /**
     * Get checkout preview
     *
     * @param string|null $cartKey    Cart key (for guest)
     * @param int|null    $customerId Customer ID (for logged-in user)
     */
    public function onGet(string|null $cartKey = null, int|null $customerId = null): static
    {
        $cart = $this->cartQuery->findByKeyOrCustomerId($cartKey, $customerId);

        if ($cart === null || empty($cart['items'])) {
            $this->code = 400;
            $this->body = ['error' => 'Cart is empty'];

            return $this;
        }

        $this->body = $this->checkoutService->preview($cart);

        return $this;
    }

    /**
     * Complete checkout and create order
     *
     * @param string|null $cartKey     Cart key (for guest)
     * @param int|null    $customerId  Customer ID (for logged-in user)
     * @param int         $paymentId   Payment method ID
     * @param int         $deliveryId  Delivery method ID
     * @param string      $name01      Last name
     * @param string      $name02      First name
     * @param string      $postalCode  Postal code
     * @param int         $prefId      Prefecture ID
     * @param string      $addr01      Address 1
     * @param string      $addr02      Address 2
     * @param string|null $phoneNumber Phone number
     * @param string|null $email       Email (required for guest)
     * @param string|null $message     Message to shop
     * @param int|null    $usePoint    Point to use
     */
    public function onPost(
        string|null $cartKey,
        int|null $customerId,
        int $paymentId,
        int $deliveryId,
        string $name01,
        string $name02,
        string $postalCode,
        int $prefId,
        string $addr01,
        string $addr02,
        string|null $phoneNumber = null,
        string|null $email = null,
        string|null $message = null,
        int|null $usePoint = 0,
    ): static {
        $cart = $this->cartQuery->findByKeyOrCustomerId($cartKey, $customerId);

        if ($cart === null || empty($cart['items'])) {
            $this->code = 400;
            $this->body = ['error' => 'Cart is empty'];

            return $this;
        }

        try {
            $orderId = $this->checkoutService->complete([
                'cart' => $cart,
                'customer_id' => $customerId,
                'payment_id' => $paymentId,
                'delivery_id' => $deliveryId,
                'name01' => $name01,
                'name02' => $name02,
                'postal_code' => $postalCode,
                'pref_id' => $prefId,
                'addr01' => $addr01,
                'addr02' => $addr02,
                'phone_number' => $phoneNumber,
                'email' => $email,
                'message' => $message,
                'use_point' => $usePoint,
            ]);

            // Clear cart after successful order
            $this->cartQuery->clear($cartKey, $customerId);

            $this->code = 201;
            $this->headers['Location'] = "/orders/{$orderId}";
            $this->body = [
                'order_id' => $orderId,
                'order' => $this->orderQuery->findById($orderId),
            ];
        } catch (Throwable $e) {
            $this->code = 400;
            $this->body = ['error' => $e->getMessage()];
        }

        return $this;
    }
}
