<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\CouponQueryInterface;
use BEAR\Resource\ResourceObject;

class Coupons extends ResourceObject
{
    public function __construct(
        private readonly CouponQueryInterface $query,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(int|null $id = null, bool $include_disabled = false): static
    {
        if ($id !== null) {
            $coupon = $this->query->findById($id);
            if ($coupon === null) {
                $this->code = 404;
                $this->body = ['error' => 'Coupon not found'];

                return $this;
            }

            $coupon['usage_count'] = $this->query->getUsageCount($id);
            $this->body = $coupon;
        } else {
            $this->body = ['coupons' => $this->query->findAll($include_disabled)];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(
        string $coupon_cd,
        string $coupon_name,
        int $coupon_type_id = 1,
        float $discount_price = 0,
        float $discount_rate = 0,
        float|null $coupon_lower_limit = null,
        int|null $coupon_use_time = null,
        bool $coupon_member = false,
        string|null $available_from_date = null,
        string|null $available_to_date = null,
    ): static {
        $existingCoupon = $this->query->findByCode($coupon_cd);
        if ($existingCoupon !== null) {
            $this->code = 409;
            $this->body = ['error' => 'Coupon code already exists'];

            return $this;
        }

        $id = $this->query->create([
            'coupon_cd' => $coupon_cd,
            'coupon_name' => $coupon_name,
            'coupon_type_id' => $coupon_type_id,
            'discount_price' => $discount_price,
            'discount_rate' => $discount_rate,
            'coupon_lower_limit' => $coupon_lower_limit,
            'coupon_use_time' => $coupon_use_time,
            'coupon_member' => $coupon_member ? 1 : 0,
            'available_from_date' => $available_from_date,
            'available_to_date' => $available_to_date,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id, 'coupon_cd' => $coupon_cd];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        string|null $coupon_name = null,
        int|null $coupon_type_id = null,
        float|null $discount_price = null,
        float|null $discount_rate = null,
        float|null $coupon_lower_limit = null,
        int|null $coupon_use_time = null,
        bool|null $coupon_member = null,
        bool|null $enable_flag = null,
        string|null $available_from_date = null,
        string|null $available_to_date = null,
    ): static {
        $coupon = $this->query->findById($id);
        if ($coupon === null) {
            $this->code = 404;
            $this->body = ['error' => 'Coupon not found'];

            return $this;
        }

        $data = [];
        if ($coupon_name !== null) {
            $data['coupon_name'] = $coupon_name;
        }

        if ($coupon_type_id !== null) {
            $data['coupon_type_id'] = $coupon_type_id;
        }

        if ($discount_price !== null) {
            $data['discount_price'] = $discount_price;
        }

        if ($discount_rate !== null) {
            $data['discount_rate'] = $discount_rate;
        }

        if ($coupon_lower_limit !== null) {
            $data['coupon_lower_limit'] = $coupon_lower_limit;
        }

        if ($coupon_use_time !== null) {
            $data['coupon_use_time'] = $coupon_use_time;
        }

        if ($coupon_member !== null) {
            $data['coupon_member'] = $coupon_member ? 1 : 0;
        }

        if ($enable_flag !== null) {
            $data['enable_flag'] = $enable_flag ? 1 : 0;
        }

        if ($available_from_date !== null) {
            $data['available_from_date'] = $available_from_date;
        }

        if ($available_to_date !== null) {
            $data['available_to_date'] = $available_to_date;
        }

        if (! empty($data)) {
            $this->query->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $coupon = $this->query->findById($id);
        if ($coupon === null) {
            $this->code = 404;
            $this->body = ['error' => 'Coupon not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
