<?php

declare(strict_types=1);

namespace BearEccube\Service;

use BearEccube\Entity\Master\OrderStatus;
use BearEccube\Query\CustomerQueryInterface;
use BearEccube\Query\DeliveryQueryInterface;
use BearEccube\Query\OrderItemQueryInterface;
use BearEccube\Query\OrderQueryInterface;
use BearEccube\Query\PaymentQueryInterface;
use BearEccube\Query\ProductClassQueryInterface;
use BearEccube\Query\ShippingQueryInterface;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * Checkout service implementation
 */
class CheckoutService implements CheckoutServiceInterface
{
    private const TAX_RATE = '0.10'; // 10% tax rate

    public function __construct(
        private readonly OrderQueryInterface $orderQuery,
        private readonly OrderItemQueryInterface $orderItemQuery,
        private readonly ShippingQueryInterface $shippingQuery,
        private readonly ProductClassQueryInterface $productClassQuery,
        private readonly PaymentQueryInterface $paymentQuery,
        private readonly DeliveryQueryInterface $deliveryQuery,
        private readonly CustomerQueryInterface $customerQuery,
    ) {
    }

    /**
     * @inheritDoc
     */
    public function preview(array $cart): array
    {
        $items = $cart['items'] ?? [];
        $subtotal = '0';
        $productItems = [];

        foreach ($items as $item) {
            $itemTotal = bcmul($item['price'], (string)$item['quantity']);
            $subtotal = bcadd($subtotal, $itemTotal);

            $productItems[] = [
                'product_class_id' => $item['product_class_id'],
                'product_name' => $item['product_name'] ?? '',
                'class_name' => $item['class_name'] ?? '',
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'total' => $itemTotal,
            ];
        }

        // Calculate tax
        $tax = bcmul($subtotal, self::TAX_RATE, 0);

        // Calculate total (simplified - delivery fee and charge not included in preview)
        $total = bcadd($subtotal, $tax);

        return [
            'items' => $productItems,
            'subtotal' => $subtotal,
            'tax' => $tax,
            'tax_rate' => self::TAX_RATE,
            'delivery_fee' => '0', // Will be calculated based on delivery method
            'charge' => '0', // Will be calculated based on payment method
            'total' => $total,
        ];
    }

    /**
     * @inheritDoc
     */
    public function complete(array $data): int
    {
        $cart = $data['cart'];
        $items = $cart['items'] ?? [];

        if (empty($items)) {
            throw new \RuntimeException('Cart is empty');
        }

        // Generate order number
        $orderNo = $this->generateOrderNo();

        // Calculate totals
        $preview = $this->preview($cart);
        $subtotal = $preview['subtotal'];
        $tax = $preview['tax'];

        // Get delivery fee
        $deliveryFee = $this->calculateDeliveryFee($data['delivery_id'], $data['pref_id']);

        // Get payment charge
        $paymentCharge = $this->calculatePaymentCharge($data['payment_id']);

        // Calculate final total
        $total = bcadd(bcadd(bcadd($subtotal, $tax), $deliveryFee), $paymentCharge);

        // Apply point discount
        $usePoint = $data['use_point'] ?? 0;
        $paymentTotal = bcsub($total, (string)$usePoint);

        if (bccomp($paymentTotal, '0') < 0) {
            $paymentTotal = '0';
        }

        // Create order
        $orderId = $this->orderQuery->create([
            'order_no' => $orderNo,
            'customer_id' => $data['customer_id'],
            'order_status_id' => OrderStatus::NEW,
            'payment_id' => $data['payment_id'],
            'name01' => $data['name01'],
            'name02' => $data['name02'],
            'postal_code' => $data['postal_code'],
            'pref_id' => $data['pref_id'],
            'addr01' => $data['addr01'],
            'addr02' => $data['addr02'],
            'phone_number' => $data['phone_number'],
            'email' => $data['email'],
            'message' => $data['message'],
            'subtotal' => $subtotal,
            'tax' => $tax,
            'delivery_fee_total' => $deliveryFee,
            'charge' => $paymentCharge,
            'discount' => '0',
            'total' => $total,
            'payment_total' => $paymentTotal,
            'add_point' => $this->calculateAddPoint($subtotal),
            'use_point' => $usePoint,
            'order_date' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);

        // Create shipping
        $shippingId = $this->shippingQuery->create([
            'order_id' => $orderId,
            'delivery_id' => $data['delivery_id'],
            'name01' => $data['name01'],
            'name02' => $data['name02'],
            'postal_code' => $data['postal_code'],
            'pref_id' => $data['pref_id'],
            'addr01' => $data['addr01'],
            'addr02' => $data['addr02'],
            'phone_number' => $data['phone_number'],
        ]);

        // Create order items
        $orderItems = [];
        foreach ($items as $item) {
            $orderItems[] = [
                'shipping_id' => $shippingId,
                'product_id' => $item['product_id'] ?? null,
                'product_class_id' => $item['product_class_id'],
                'product_name' => $item['product_name'] ?? '',
                'product_code' => $item['product_code'] ?? '',
                'class_category_name1' => $item['class_category_name1'] ?? null,
                'class_category_name2' => $item['class_category_name2'] ?? null,
                'price' => $item['price'],
                'quantity' => $item['quantity'],
                'tax' => bcmul($item['price'], self::TAX_RATE, 0),
                'tax_rate' => self::TAX_RATE,
            ];

            // Update stock
            $this->productClassQuery->updateStock(
                $item['product_class_id'],
                -$item['quantity']
            );
        }

        $this->orderItemQuery->createItems($orderId, $orderItems);

        // Update customer point
        if ($data['customer_id'] !== null) {
            $addPoint = $this->calculateAddPoint($subtotal);
            $pointChange = $addPoint - $usePoint;
            $this->customerQuery->updatePoint($data['customer_id'], $pointChange);
        }

        return $orderId;
    }

    private function generateOrderNo(): string
    {
        $date = (new DateTimeImmutable())->format('Ymd');
        $uuid = substr(Uuid::uuid4()->toString(), 0, 8);
        return $date . '-' . strtoupper($uuid);
    }

    private function calculateDeliveryFee(int $deliveryId, int $prefId): string
    {
        // This should be fetched from delivery_fee table based on delivery method and prefecture
        // Simplified implementation
        return '500';
    }

    private function calculatePaymentCharge(int $paymentId): string
    {
        // This should be fetched from payment table
        // Simplified implementation
        return '0';
    }

    private function calculateAddPoint(string $subtotal): int
    {
        // 1% point back (simplified)
        return (int)bcmul($subtotal, '0.01', 0);
    }
}
