<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Query\CouponQueryInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function bccomp;
use function bcdiv;
use function bcmul;
use function bcsub;

/**
 * Coupons resource (クーポン)
 */
class Coupons extends ResourceObject
{
    #[Inject]
    public function __construct(private CouponQueryInterface $couponQuery)
    {
    }

    /**
     * Validate and get coupon by code
     *
     * @param string $code Coupon code
     */
    public function onGet(string $code): static
    {
        $coupon = $this->couponQuery->findByCode($code);

        if ($coupon === null) {
            $this->code = 404;
            $this->body = ['error' => 'Coupon not found'];

            return $this;
        }

        if (! $coupon['is_available']) {
            $this->code = 400;
            $this->body = ['error' => 'Coupon is not available'];

            return $this;
        }

        $this->body = $coupon;

        return $this;
    }

    /**
     * Apply coupon to calculate discount
     *
     * @param string $code     Coupon code
     * @param string $subtotal Cart subtotal
     */
    public function onPost(string $code, string $subtotal): static
    {
        $coupon = $this->couponQuery->findByCode($code);

        if ($coupon === null) {
            $this->code = 404;
            $this->body = ['error' => 'Coupon not found'];

            return $this;
        }

        if (! $coupon['is_available']) {
            $this->code = 400;
            $this->body = ['error' => 'Coupon is not available'];

            return $this;
        }

        // Check minimum order amount
        if ($coupon['coupon_lower_limit'] && bccomp($subtotal, $coupon['coupon_lower_limit']) < 0) {
            $this->code = 400;
            $this->body = [
                'error' => 'Order amount is below minimum',
                'minimum' => $coupon['coupon_lower_limit'],
            ];

            return $this;
        }

        // Calculate discount
        $discount = $this->calculateDiscount($coupon, $subtotal);

        $this->body = [
            'coupon' => $coupon,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'total' => bcsub($subtotal, $discount),
        ];

        return $this;
    }

    private function calculateDiscount(array $coupon, string $subtotal): string
    {
        if ($coupon['discount_type'] === 'rate') {
            return bcmul($subtotal, bcdiv($coupon['discount_rate'], '100', 4), 0);
        }

        return $coupon['discount_price'];
    }
}
