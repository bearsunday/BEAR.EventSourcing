<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Coupon-Order relation entity (クーポン利用履歴)
 */
class CouponOrder extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $couponId = null;
    protected ?Coupon $coupon = null;
    protected ?int $orderId = null;
    protected ?Order $order = null;
    protected ?int $customerId = null;
    protected ?Customer $customer = null;
    protected string $couponCd = '';
    protected string $couponName = '';
    protected string $discountPrice = '0';
    protected bool $visible = true;
    protected string $orderChangeStatus = '0';

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getCouponId(): ?int { return $this->couponId; }
    public function setCouponId(?int $couponId): static { $this->couponId = $couponId; return $this; }

    public function getCoupon(): ?Coupon { return $this->coupon; }
    public function setCoupon(?Coupon $coupon): static { $this->coupon = $coupon; return $this; }

    public function getOrderId(): ?int { return $this->orderId; }
    public function setOrderId(?int $orderId): static { $this->orderId = $orderId; return $this; }

    public function getOrder(): ?Order { return $this->order; }
    public function setOrder(?Order $order): static { $this->order = $order; return $this; }

    public function getCustomerId(): ?int { return $this->customerId; }
    public function setCustomerId(?int $customerId): static { $this->customerId = $customerId; return $this; }

    public function getCustomer(): ?Customer { return $this->customer; }
    public function setCustomer(?Customer $customer): static { $this->customer = $customer; return $this; }

    public function getCouponCd(): string { return $this->couponCd; }
    public function setCouponCd(string $couponCd): static { $this->couponCd = $couponCd; return $this; }

    public function getCouponName(): string { return $this->couponName; }
    public function setCouponName(string $couponName): static { $this->couponName = $couponName; return $this; }

    public function getDiscountPrice(): string { return $this->discountPrice; }
    public function setDiscountPrice(string $discountPrice): static { $this->discountPrice = $discountPrice; return $this; }

    public function isVisible(): bool { return $this->visible; }
    public function setVisible(bool $visible): static { $this->visible = $visible; return $this; }
}
