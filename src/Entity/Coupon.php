<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

use function bccomp;
use function bcdiv;
use function bcmul;

/**
 * Coupon entity (クーポン)
 */
class Coupon extends AbstractEntity
{
    protected int|null $id = null;
    protected string $couponCd = '';
    protected string $couponName = '';
    protected int $couponType = 1;
    protected string $discountType = 'price'; // 'price' or 'rate'
    protected string $discountPrice = '0';
    protected string $discountRate = '0';
    protected string|null $couponLowerLimit = null;
    protected DateTimeImmutable|null $availableFromDate = null;
    protected DateTimeImmutable|null $availableToDate = null;
    protected int $couponUseTime = 0; // 0 = unlimited
    protected int $usedTime = 0;
    protected bool $visible = true;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

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

    public function getCouponType(): int
    {
        return $this->couponType;
    }

    public function setCouponType(int $couponType): static
    {
        $this->couponType = $couponType;

        return $this;
    }

    public function getDiscountType(): string
    {
        return $this->discountType;
    }

    public function setDiscountType(string $discountType): static
    {
        $this->discountType = $discountType;

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

    public function getDiscountRate(): string
    {
        return $this->discountRate;
    }

    public function setDiscountRate(string $discountRate): static
    {
        $this->discountRate = $discountRate;

        return $this;
    }

    public function getCouponLowerLimit(): string|null
    {
        return $this->couponLowerLimit;
    }

    public function setCouponLowerLimit(string|null $couponLowerLimit): static
    {
        $this->couponLowerLimit = $couponLowerLimit;

        return $this;
    }

    public function getAvailableFromDate(): DateTimeImmutable|null
    {
        return $this->availableFromDate;
    }

    public function setAvailableFromDate(DateTimeImmutable|null $availableFromDate): static
    {
        $this->availableFromDate = $availableFromDate;

        return $this;
    }

    public function getAvailableToDate(): DateTimeImmutable|null
    {
        return $this->availableToDate;
    }

    public function setAvailableToDate(DateTimeImmutable|null $availableToDate): static
    {
        $this->availableToDate = $availableToDate;

        return $this;
    }

    public function getCouponUseTime(): int
    {
        return $this->couponUseTime;
    }

    public function setCouponUseTime(int $couponUseTime): static
    {
        $this->couponUseTime = $couponUseTime;

        return $this;
    }

    public function getUsedTime(): int
    {
        return $this->usedTime;
    }

    public function setUsedTime(int $usedTime): static
    {
        $this->usedTime = $usedTime;

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

    /**
     * Check if coupon is currently available
     */
    public function isAvailable(): bool
    {
        if (! $this->visible) {
            return false;
        }

        $now = new DateTimeImmutable();
        if ($this->availableFromDate && $now < $this->availableFromDate) {
            return false;
        }

        if ($this->availableToDate && $now > $this->availableToDate) {
            return false;
        }

        return $this->couponUseTime <= 0 || $this->usedTime < $this->couponUseTime;
    }

    /**
     * Calculate discount amount
     */
    public function calculateDiscount(string $subtotal): string
    {
        if ($this->couponLowerLimit && bccomp($subtotal, $this->couponLowerLimit) < 0) {
            return '0';
        }

        if ($this->discountType === 'rate') {
            return bcmul($subtotal, bcdiv($this->discountRate, '100', 4), 0);
        }

        return $this->discountPrice;
    }
}
