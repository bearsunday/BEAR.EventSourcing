<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\Resource\ResourceObject;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function implode;

class BaseInfo extends ResourceObject
{
    public function __construct(
        private readonly ExtendedPdo $pdo,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(): static
    {
        $baseInfo = $this->pdo->fetchOne('SELECT * FROM base_info WHERE id = 1');
        if ($baseInfo === false) {
            $this->code = 404;
            $this->body = ['error' => 'Base info not found'];

            return $this;
        }

        $this->body = $baseInfo;

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        string|null $shop_name = null,
        string|null $company_name = null,
        string|null $company_kana = null,
        string|null $shop_kana = null,
        string|null $shop_name_eng = null,
        string|null $postal_code = null,
        int|null $pref_id = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phone_number = null,
        string|null $business_hour = null,
        string|null $email01 = null,
        string|null $email02 = null,
        string|null $email03 = null,
        string|null $email04 = null,
        string|null $good_traded = null,
        string|null $message = null,
        float|null $delivery_free_amount = null,
        int|null $delivery_free_quantity = null,
    ): static {
        $data = [];
        if ($shop_name !== null) {
            $data['shop_name'] = $shop_name;
        }

        if ($company_name !== null) {
            $data['company_name'] = $company_name;
        }

        if ($company_kana !== null) {
            $data['company_kana'] = $company_kana;
        }

        if ($shop_kana !== null) {
            $data['shop_kana'] = $shop_kana;
        }

        if ($shop_name_eng !== null) {
            $data['shop_name_eng'] = $shop_name_eng;
        }

        if ($postal_code !== null) {
            $data['postal_code'] = $postal_code;
        }

        if ($pref_id !== null) {
            $data['pref_id'] = $pref_id;
        }

        if ($addr01 !== null) {
            $data['addr01'] = $addr01;
        }

        if ($addr02 !== null) {
            $data['addr02'] = $addr02;
        }

        if ($phone_number !== null) {
            $data['phone_number'] = $phone_number;
        }

        if ($business_hour !== null) {
            $data['business_hour'] = $business_hour;
        }

        if ($email01 !== null) {
            $data['email01'] = $email01;
        }

        if ($email02 !== null) {
            $data['email02'] = $email02;
        }

        if ($email03 !== null) {
            $data['email03'] = $email03;
        }

        if ($email04 !== null) {
            $data['email04'] = $email04;
        }

        if ($good_traded !== null) {
            $data['good_traded'] = $good_traded;
        }

        if ($message !== null) {
            $data['message'] = $message;
        }

        if ($delivery_free_amount !== null) {
            $data['delivery_free_amount'] = $delivery_free_amount;
        }

        if ($delivery_free_quantity !== null) {
            $data['delivery_free_quantity'] = $delivery_free_quantity;
        }

        if (! empty($data)) {
            $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
            $this->pdo->perform('UPDATE base_info SET ' . implode(', ', $sets) . ' WHERE id = 1', $data);
        }

        $this->code = 200;
        $this->body = ['updated' => true];

        return $this;
    }
}
