<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Pref;

/**
 * Customer address entity (会員配送先)
 */
class CustomerAddress extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $customerId = null;
    protected Customer|null $customer = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected string|null $kana01 = null;
    protected string|null $kana02 = null;
    protected string|null $companyName = null;
    protected string|null $postalCode = null;
    protected Pref|null $pref = null;
    protected string|null $addr01 = null;
    protected string|null $addr02 = null;
    protected string|null $phoneNumber = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

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

    public function getName01(): string
    {
        return $this->name01;
    }

    public function setName01(string $name01): static
    {
        $this->name01 = $name01;

        return $this;
    }

    public function getName02(): string
    {
        return $this->name02;
    }

    public function setName02(string $name02): static
    {
        $this->name02 = $name02;

        return $this;
    }

    public function getKana01(): string|null
    {
        return $this->kana01;
    }

    public function setKana01(string|null $kana01): static
    {
        $this->kana01 = $kana01;

        return $this;
    }

    public function getKana02(): string|null
    {
        return $this->kana02;
    }

    public function setKana02(string|null $kana02): static
    {
        $this->kana02 = $kana02;

        return $this;
    }

    public function getCompanyName(): string|null
    {
        return $this->companyName;
    }

    public function setCompanyName(string|null $companyName): static
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getPostalCode(): string|null
    {
        return $this->postalCode;
    }

    public function setPostalCode(string|null $postalCode): static
    {
        $this->postalCode = $postalCode;

        return $this;
    }

    public function getPref(): Pref|null
    {
        return $this->pref;
    }

    public function setPref(Pref|null $pref): static
    {
        $this->pref = $pref;

        return $this;
    }

    public function getAddr01(): string|null
    {
        return $this->addr01;
    }

    public function setAddr01(string|null $addr01): static
    {
        $this->addr01 = $addr01;

        return $this;
    }

    public function getAddr02(): string|null
    {
        return $this->addr02;
    }

    public function setAddr02(string|null $addr02): static
    {
        $this->addr02 = $addr02;

        return $this;
    }

    public function getPhoneNumber(): string|null
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(string|null $phoneNumber): static
    {
        $this->phoneNumber = $phoneNumber;

        return $this;
    }

    /**
     * Get full name
     */
    public function getName(): string
    {
        return $this->name01 . ' ' . $this->name02;
    }

    /**
     * Get full address
     */
    public function getAddress(): string
    {
        $prefName = $this->pref?->getName() ?? '';

        return $prefName . ($this->addr01 ?? '') . ($this->addr02 ?? '');
    }
}
