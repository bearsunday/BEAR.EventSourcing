<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\CustomerStatus;
use BEAR\EventSourcing\Entity\Master\Pref;
use BEAR\EventSourcing\Entity\Master\Sex;
use DateTimeImmutable;

/**
 * Customer entity (会員)
 */
class Customer extends AbstractEntity
{
    protected int|null $id = null;
    protected string $email = '';
    protected string|null $password = null;
    protected string|null $salt = null;
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
    protected DateTimeImmutable|null $birth = null;
    protected Sex|null $sex = null;
    protected CustomerStatus|null $status = null;
    protected string|null $secretKey = null;
    protected string|null $resetKey = null;
    protected DateTimeImmutable|null $resetExpire = null;
    protected int $point = 0;
    protected DateTimeImmutable|null $firstBuyDate = null;
    protected DateTimeImmutable|null $lastBuyDate = null;
    protected string $buyTimes = '0';
    protected string $buyTotal = '0';
    protected string|null $note = null;
    /** @var CustomerAddress[] */
    protected array $customerAddresses = [];
    /** @var Order[] */
    protected array $orders = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getPassword(): string|null
    {
        return $this->password;
    }

    public function setPassword(string|null $password): static
    {
        $this->password = $password;

        return $this;
    }

    public function getSalt(): string|null
    {
        return $this->salt;
    }

    public function setSalt(string|null $salt): static
    {
        $this->salt = $salt;

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

    public function getBirth(): DateTimeImmutable|null
    {
        return $this->birth;
    }

    public function setBirth(DateTimeImmutable|null $birth): static
    {
        $this->birth = $birth;

        return $this;
    }

    public function getSex(): Sex|null
    {
        return $this->sex;
    }

    public function setSex(Sex|null $sex): static
    {
        $this->sex = $sex;

        return $this;
    }

    public function getStatus(): CustomerStatus|null
    {
        return $this->status;
    }

    public function setStatus(CustomerStatus|null $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getSecretKey(): string|null
    {
        return $this->secretKey;
    }

    public function setSecretKey(string|null $secretKey): static
    {
        $this->secretKey = $secretKey;

        return $this;
    }

    public function getResetKey(): string|null
    {
        return $this->resetKey;
    }

    public function setResetKey(string|null $resetKey): static
    {
        $this->resetKey = $resetKey;

        return $this;
    }

    public function getResetExpire(): DateTimeImmutable|null
    {
        return $this->resetExpire;
    }

    public function setResetExpire(DateTimeImmutable|null $resetExpire): static
    {
        $this->resetExpire = $resetExpire;

        return $this;
    }

    public function getPoint(): int
    {
        return $this->point;
    }

    public function setPoint(int $point): static
    {
        $this->point = $point;

        return $this;
    }

    public function getFirstBuyDate(): DateTimeImmutable|null
    {
        return $this->firstBuyDate;
    }

    public function setFirstBuyDate(DateTimeImmutable|null $firstBuyDate): static
    {
        $this->firstBuyDate = $firstBuyDate;

        return $this;
    }

    public function getLastBuyDate(): DateTimeImmutable|null
    {
        return $this->lastBuyDate;
    }

    public function setLastBuyDate(DateTimeImmutable|null $lastBuyDate): static
    {
        $this->lastBuyDate = $lastBuyDate;

        return $this;
    }

    public function getBuyTimes(): string
    {
        return $this->buyTimes;
    }

    public function setBuyTimes(string $buyTimes): static
    {
        $this->buyTimes = $buyTimes;

        return $this;
    }

    public function getBuyTotal(): string
    {
        return $this->buyTotal;
    }

    public function setBuyTotal(string $buyTotal): static
    {
        $this->buyTotal = $buyTotal;

        return $this;
    }

    public function getNote(): string|null
    {
        return $this->note;
    }

    public function setNote(string|null $note): static
    {
        $this->note = $note;

        return $this;
    }

    /** @return CustomerAddress[] */
    public function getCustomerAddresses(): array
    {
        return $this->customerAddresses;
    }

    /** @param CustomerAddress[] $customerAddresses */
    public function setCustomerAddresses(array $customerAddresses): static
    {
        $this->customerAddresses = $customerAddresses;

        return $this;
    }

    /** @return Order[] */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /** @param Order[] $orders */
    public function setOrders(array $orders): static
    {
        $this->orders = $orders;

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
     * Get full kana
     */
    public function getKana(): string
    {
        return ($this->kana01 ?? '') . ' ' . ($this->kana02 ?? '');
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
