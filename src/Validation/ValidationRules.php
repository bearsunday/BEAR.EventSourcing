<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Validation;

/**
 * Common validation rules for entities
 */
final class ValidationRules
{
    public static function customerRegistration(): array
    {
        return [
            'email' => 'required|email|max:255',
            'password' => 'required|password|confirmed',
            'name01' => 'required|string|max:255',
            'name02' => 'required|string|max:255',
            'kana01' => 'string|max:255',
            'kana02' => 'string|max:255',
            'postal_code' => 'postal_code',
            'phone_number' => 'phone',
        ];
    }

    public static function customerUpdate(): array
    {
        return [
            'name01' => 'string|max:255',
            'name02' => 'string|max:255',
            'kana01' => 'string|max:255',
            'kana02' => 'string|max:255',
            'postal_code' => 'postal_code',
            'phone_number' => 'phone',
        ];
    }

    public static function login(): array
    {
        return [
            'email' => 'required|email',
            'password' => 'required',
        ];
    }

    public static function product(): array
    {
        return [
            'name' => 'required|string|max:255',
            'product_status_id' => 'required|integer|in:1,2,3',
            'description_list' => 'string',
            'description_detail' => 'string',
        ];
    }

    public static function productClass(): array
    {
        return [
            'price02' => 'required|numeric|min:0',
            'stock' => 'integer|min:0',
            'sale_type_id' => 'required|integer',
        ];
    }

    public static function order(): array
    {
        return [
            'name01' => 'required|string|max:255',
            'name02' => 'required|string|max:255',
            'email' => 'required|email|max:255',
            'phone_number' => 'required|phone',
            'postal_code' => 'required|postal_code',
            'pref_id' => 'required|integer',
            'addr01' => 'required|string|max:255',
            'addr02' => 'string|max:255',
        ];
    }

    public static function coupon(): array
    {
        return [
            'coupon_cd' => 'required|string|max:255',
            'coupon_name' => 'required|string|max:255',
            'coupon_type_id' => 'required|integer|in:1,2',
            'discount_price' => 'numeric|min:0',
            'discount_rate' => 'numeric|min:0|max:100',
        ];
    }

    public static function review(): array
    {
        return [
            'product_id' => 'required|integer',
            'title' => 'required|string|max:255',
            'comment' => 'required|string',
            'recommend_level' => 'required|integer|in:1,2,3,4,5',
        ];
    }

    public static function news(): array
    {
        return [
            'title' => 'required|string|max:255',
            'publish_date' => 'required|date',
            'comment' => 'string',
            'url' => 'string|max:255',
        ];
    }

    public static function member(): array
    {
        return [
            'name' => 'required|string|max:255',
            'login_id' => 'required|string|max:255',
            'password' => 'required|password',
            'department' => 'string|max:255',
            'authority_id' => 'required|integer|in:0,1',
        ];
    }

    public static function taxRule(): array
    {
        return [
            'tax_rate' => 'required|numeric|min:0|max:100',
            'apply_date' => 'required|date',
            'calc_rule' => 'integer|in:1,2,3,4,5,6',
        ];
    }
}
