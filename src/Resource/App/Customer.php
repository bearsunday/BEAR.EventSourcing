<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BearEccube\Query\CustomerQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Customer resource (会員詳細)
 *
 * @Link(rel="orders", href="/customers/{id}/orders")
 * @Link(rel="addresses", href="/customers/{id}/addresses")
 */
class Customer extends ResourceObject
{
    private CustomerQueryInterface $customerQuery;

    #[Inject]
    public function __construct(CustomerQueryInterface $customerQuery)
    {
        $this->customerQuery = $customerQuery;
    }

    /**
     * Get customer by ID
     *
     * @param int $id Customer ID
     */
    public function onGet(int $id): static
    {
        $customer = $this->customerQuery->findById($id);

        if ($customer === null) {
            $this->code = 404;
            $this->body = ['error' => 'Customer not found'];
            return $this;
        }

        $this->body = $customer;

        return $this;
    }

    /**
     * Update customer
     *
     * @param int         $id         Customer ID
     * @param string|null $email      Email address
     * @param string|null $name01     Last name
     * @param string|null $name02     First name
     * @param string|null $kana01     Last name (kana)
     * @param string|null $kana02     First name (kana)
     * @param string|null $postalCode Postal code
     * @param int|null    $prefId     Prefecture ID
     * @param string|null $addr01     Address 1
     * @param string|null $addr02     Address 2
     * @param string|null $phone      Phone number
     * @param int|null    $status     Customer status ID
     */
    public function onPut(
        int $id,
        ?string $email = null,
        ?string $name01 = null,
        ?string $name02 = null,
        ?string $kana01 = null,
        ?string $kana02 = null,
        ?string $postalCode = null,
        ?int $prefId = null,
        ?string $addr01 = null,
        ?string $addr02 = null,
        ?string $phone = null,
        ?int $status = null
    ): static {
        $customer = $this->customerQuery->findById($id);

        if ($customer === null) {
            $this->code = 404;
            $this->body = ['error' => 'Customer not found'];
            return $this;
        }

        $data = array_filter([
            'email' => $email,
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'postal_code' => $postalCode,
            'pref_id' => $prefId,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'phone_number' => $phone,
            'status_id' => $status,
        ], fn($v) => $v !== null);

        $this->customerQuery->update($id, $data);

        $this->body = $this->customerQuery->findById($id);

        return $this;
    }

    /**
     * Delete customer
     *
     * @param int $id Customer ID
     */
    public function onDelete(int $id): static
    {
        $customer = $this->customerQuery->findById($id);

        if ($customer === null) {
            $this->code = 404;
            $this->body = ['error' => 'Customer not found'];
            return $this;
        }

        $this->customerQuery->delete($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
