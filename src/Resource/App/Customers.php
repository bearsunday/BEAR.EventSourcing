<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App;

use BEAR\Resource\Annotation\Link;
use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Query\CustomerQueryInterface;
use Ray\Di\Di\Inject;

/**
 * Customers resource (会員一覧)
 *
 * @Link(rel="customer", href="/customers/{id}")
 */
class Customers extends ResourceObject
{
    private CustomerQueryInterface $customerQuery;

    #[Inject]
    public function __construct(CustomerQueryInterface $customerQuery)
    {
        $this->customerQuery = $customerQuery;
    }

    /**
     * Get customer list
     *
     * @param string|null $email  Email to search
     * @param string|null $name   Name to search
     * @param int|null    $status Customer status ID
     * @param int         $page   Page number
     * @param int         $limit  Items per page
     */
    public function onGet(
        ?string $email = null,
        ?string $name = null,
        ?int $status = null,
        int $page = 1,
        int $limit = 20
    ): static {
        $offset = ($page - 1) * $limit;

        $this->body = [
            'customers' => $this->customerQuery->findAll($email, $name, $status, $limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->customerQuery->count($email, $name, $status),
            ],
        ];

        return $this;
    }

    /**
     * Register a new customer
     *
     * @param string      $email      Email address
     * @param string      $password   Password
     * @param string      $name01     Last name
     * @param string      $name02     First name
     * @param string|null $kana01     Last name (kana)
     * @param string|null $kana02     First name (kana)
     * @param string|null $postalCode Postal code
     * @param int|null    $prefId     Prefecture ID
     * @param string|null $addr01     Address 1
     * @param string|null $addr02     Address 2
     * @param string|null $phone      Phone number
     */
    public function onPost(
        string $email,
        string $password,
        string $name01,
        string $name02,
        ?string $kana01 = null,
        ?string $kana02 = null,
        ?string $postalCode = null,
        ?int $prefId = null,
        ?string $addr01 = null,
        ?string $addr02 = null,
        ?string $phone = null
    ): static {
        $id = $this->customerQuery->create([
            'email' => $email,
            'password' => password_hash($password, PASSWORD_DEFAULT),
            'name01' => $name01,
            'name02' => $name02,
            'kana01' => $kana01,
            'kana02' => $kana02,
            'postal_code' => $postalCode,
            'pref_id' => $prefId,
            'addr01' => $addr01,
            'addr02' => $addr02,
            'phone_number' => $phone,
        ]);

        $this->code = 201;
        $this->headers['Location'] = "/customers/{$id}";
        $this->body = ['id' => $id];

        return $this;
    }
}
