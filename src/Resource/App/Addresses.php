<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\CustomerAddressQueryInterface;
use BEAR\Resource\ResourceObject;

class Addresses extends ResourceObject
{
    public function __construct(
        private readonly CustomerAddressQueryInterface $query,
    ) {
    }

    #[RequireAuth]
    public function onGet(int $customer_id, int|null $id = null): static
    {
        if ($id !== null) {
            $address = $this->query->findById($id);
            if ($address === null || $address['customer_id'] !== $customer_id) {
                $this->code = 404;
                $this->body = ['error' => 'Address not found'];

                return $this;
            }

            $this->body = $address;
        } else {
            $this->body = ['addresses' => $this->query->findByCustomerId($customer_id)];
        }

        return $this;
    }

    #[RequireAuth]
    public function onPost(
        int $customer_id,
        string $name01,
        string $name02,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $company_name = null,
        string|null $postal_code = null,
        int|null $pref_id = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phone_number = null,
        bool $is_default = false,
    ): static {
        $id = $this->query->create([
            'customer_id' => $customer_id,
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'company_name' => $company_name,
            'postal_code' => $postal_code,
            'pref_id' => $pref_id,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'phone_number' => $phone_number,
            'is_default' => $is_default ? 1 : 0,
        ]);

        if ($is_default) {
            $this->query->setDefault($customer_id, $id);
        }

        $this->code = 201;
        $this->body = ['id' => $id];

        return $this;
    }

    #[RequireAuth]
    public function onPut(
        int $customer_id,
        int $id,
        string|null $name01 = null,
        string|null $name02 = null,
        string|null $kana01 = null,
        string|null $kana02 = null,
        string|null $company_name = null,
        string|null $postal_code = null,
        int|null $pref_id = null,
        string|null $addr01 = null,
        string|null $addr02 = null,
        string|null $phone_number = null,
        bool|null $is_default = null,
    ): static {
        $address = $this->query->findById($id);
        if ($address === null || $address['customer_id'] !== $customer_id) {
            $this->code = 404;
            $this->body = ['error' => 'Address not found'];

            return $this;
        }

        $data = [];
        if ($name01 !== null) {
            $data['name01'] = $name01;
        }

        if ($name02 !== null) {
            $data['name02'] = $name02;
        }

        if ($kana01 !== null) {
            $data['kana01'] = $kana01;
        }

        if ($kana02 !== null) {
            $data['kana02'] = $kana02;
        }

        if ($company_name !== null) {
            $data['company_name'] = $company_name;
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

        if (! empty($data)) {
            $this->query->update($id, $data);
        }

        if ($is_default === true) {
            $this->query->setDefault($customer_id, $id);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }

    #[RequireAuth]
    public function onDelete(int $customer_id, int $id): static
    {
        $address = $this->query->findById($id);
        if ($address === null || $address['customer_id'] !== $customer_id) {
            $this->code = 404;
            $this->body = ['error' => 'Address not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
