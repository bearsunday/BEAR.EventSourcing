<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Coupon-Order relation entity (クーポン利用履歴)
 */
class CouponOrder extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $couponId = null;
    protected Coupon|null $coupon = null;
    protected int|null $orderId = null;
    protected Order|null $order = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected string $couponCd = '';
    protected string $couponName = '';
    protected string $discountPrice = '0';
    protected bool $visible = true;
    protected string $orderChangeStatus = '0';

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getCouponId(): int|null
    {
        return $this->couponId;
    }

    public function setCouponId(int|null $couponId): static
    {
        $this->couponId = $couponId;

        return $this;
    }

    public function getCoupon(): Coupon|null
    {
        return $this->coupon;
    }

    public function setCoupon(Coupon|null $coupon): static
    {
        $this->coupon = $coupon;

        return $this;
    }

    public function getOrderId(): int|null
    {
        return $this->orderId;
    }

    public function setOrderId(int|null $orderId): static
    {
        $this->orderId = $orderId;

        return $this;
    }

    public function getOrder(): Order|null
    {
        return $this->order;
    }

    public function setOrder(Order|null $order): static
    {
        $this->order = $order;

        return $this;
    }

    public function getCustomerId(): int|null
    {
        return $this->customerId;
    }

    public function setCustomerId(int|null $customerId): static
    {
        $this->customerId = $customerId;

        return $this;
    }

    public function getCustomer(): Customer|null
    {
        return $this->customer;
    }

    public function setCustomer(Customer|null $customer): static
    {
        $this->customer = $customer;

        return $this;
    }

    public function getCouponCd(): string
    {
        return $this->couponCd;
    }

    public function setCouponCd(string $couponCd): static
    {
        $this->couponCd = $couponCd;

        return $this;
    }

    public function getCouponName(): string
    {
        return $this->couponName;
    }

    public function setCouponName(string $couponName): static
    {
        $this->couponName = $couponName;

        return $this;
    }

    public function getDiscountPrice(): string
    {
        return $this->discountPrice;
    }

    public function setDiscountPrice(string $discountPrice): static
    {
        $this->discountPrice = $discountPrice;

        return $this;
    }

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;

        return $this;
    }
}
