<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use Aura\Sql\ExtendedPdo;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use DateTimeImmutable;

class BaseInfo extends ResourceObject
{
    public function __construct(
        private readonly ExtendedPdo $pdo
    ) {}

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
        ?string $shop_name = null,
        ?string $company_name = null,
        ?string $company_kana = null,
        ?string $shop_kana = null,
        ?string $shop_name_eng = null,
        ?string $postal_code = null,
        ?int $pref_id = null,
        ?string $addr01 = null,
        ?string $addr02 = null,
        ?string $phone_number = null,
        ?string $business_hour = null,
        ?string $email01 = null,
        ?string $email02 = null,
        ?string $email03 = null,
        ?string $email04 = null,
        ?string $good_traded = null,
        ?string $message = null,
        ?float $delivery_free_amount = null,
        ?int $delivery_free_quantity = null
    ): static {
        $data = [];
        if ($shop_name !== null) $data['shop_name'] = $shop_name;
        if ($company_name !== null) $data['company_name'] = $company_name;
        if ($company_kana !== null) $data['company_kana'] = $company_kana;
        if ($shop_kana !== null) $data['shop_kana'] = $shop_kana;
        if ($shop_name_eng !== null) $data['shop_name_eng'] = $shop_name_eng;
        if ($postal_code !== null) $data['postal_code'] = $postal_code;
        if ($pref_id !== null) $data['pref_id'] = $pref_id;
        if ($addr01 !== null) $data['addr01'] = $addr01;
        if ($addr02 !== null) $data['addr02'] = $addr02;
        if ($phone_number !== null) $data['phone_number'] = $phone_number;
        if ($business_hour !== null) $data['business_hour'] = $business_hour;
        if ($email01 !== null) $data['email01'] = $email01;
        if ($email02 !== null) $data['email02'] = $email02;
        if ($email03 !== null) $data['email03'] = $email03;
        if ($email04 !== null) $data['email04'] = $email04;
        if ($good_traded !== null) $data['good_traded'] = $good_traded;
        if ($message !== null) $data['message'] = $message;
        if ($delivery_free_amount !== null) $data['delivery_free_amount'] = $delivery_free_amount;
        if ($delivery_free_quantity !== null) $data['delivery_free_quantity'] = $delivery_free_quantity;

        if (!empty($data)) {
            $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
            $sets = array_map(fn($k) => "{$k} = :{$k}", array_keys($data));
            $this->pdo->perform('UPDATE base_info SET ' . implode(', ', $sets) . ' WHERE id = 1', $data);
        }

        $this->code = 200;
        $this->body = ['updated' => true];
        return $this;
    }
}
