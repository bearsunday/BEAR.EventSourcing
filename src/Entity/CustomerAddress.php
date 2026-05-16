<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Pref;

/**
 * Customer address entity (会員配送先)
 */
class CustomerAddress extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $customerId = null;
    protected ?Customer $customer = null;
    protected string $name01 = '';
    protected string $name02 = '';
    protected ?string $kana01 = null;
    protected ?string $kana02 = null;
    protected ?string $companyName = null;
    protected ?string $postalCode = null;
    protected ?Pref $pref = null;
    protected ?string $addr01 = null;
    protected ?string $addr02 = null;
    protected ?string $phoneNumber = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getCustomerId(): ?int
    {
        return $this->customerId;
    }

    public function setCustomerId(?int $customerId): static
    {
        $this->customerId = $customerId;
        return $this;
    }

    public function getCustomer(): ?Customer
    {
        return $this->customer;
    }

    public function setCustomer(?Customer $customer): static
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

    public function getKana01(): ?string
    {
        return $this->kana01;
    }

    public function setKana01(?string $kana01): static
    {
        $this->kana01 = $kana01;
        return $this;
    }

    public function getKana02(): ?string
    {
        return $this->kana02;
    }

    public function setKana02(?string $kana02): static
    {
        $this->kana02 = $kana02;
        return $this;
    }

    public function getCompanyName(): ?string
    {
        return $this->companyName;
    }

    public function setCompanyName(?string $companyName): static
    {
        $this->companyName = $companyName;
        return $this;
    }

    public function getPostalCode(): ?string
    {
        return $this->postalCode;
    }

    public function setPostalCode(?string $postalCode): static
    {
        $this->postalCode = $postalCode;
        return $this;
    }

    public function getPref(): ?Pref
    {
        return $this->pref;
    }

    public function setPref(?Pref $pref): static
    {
        $this->pref = $pref;
        return $this;
    }

    public function getAddr01(): ?string
    {
        return $this->addr01;
    }

    public function setAddr01(?string $addr01): static
    {
        $this->addr01 = $addr01;
        return $this;
    }

    public function getAddr02(): ?string
    {
        return $this->addr02;
    }

    public function setAddr02(?string $addr02): static
    {
        $this->addr02 = $addr02;
        return $this;
    }

    public function getPhoneNumber(): ?string
    {
        return $this->phoneNumber;
    }

    public function setPhoneNumber(?string $phoneNumber): static
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
