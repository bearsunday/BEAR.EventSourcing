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
    protected ?int $id = null;
    protected string $email = '';
    protected ?string $password = null;
    protected ?string $salt = null;
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
    protected ?DateTimeImmutable $birth = null;
    protected ?Sex $sex = null;
    protected ?CustomerStatus $status = null;
    protected ?string $secretKey = null;
    protected ?string $resetKey = null;
    protected ?DateTimeImmutable $resetExpire = null;
    protected int $point = 0;
    protected ?DateTimeImmutable $firstBuyDate = null;
    protected ?DateTimeImmutable $lastBuyDate = null;
    protected string $buyTimes = '0';
    protected string $buyTotal = '0';
    protected ?string $note = null;
    /** @var CustomerAddress[] */
    protected array $customerAddresses = [];
    /** @var Order[] */
    protected array $orders = [];

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
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

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(?string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getSalt(): ?string
    {
        return $this->salt;
    }

    public function setSalt(?string $salt): static
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

    public function getBirth(): ?DateTimeImmutable
    {
        return $this->birth;
    }

    public function setBirth(?DateTimeImmutable $birth): static
    {
        $this->birth = $birth;
        return $this;
    }

    public function getSex(): ?Sex
    {
        return $this->sex;
    }

    public function setSex(?Sex $sex): static
    {
        $this->sex = $sex;
        return $this;
    }

    public function getStatus(): ?CustomerStatus
    {
        return $this->status;
    }

    public function setStatus(?CustomerStatus $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function getSecretKey(): ?string
    {
        return $this->secretKey;
    }

    public function setSecretKey(?string $secretKey): static
    {
        $this->secretKey = $secretKey;
        return $this;
    }

    public function getResetKey(): ?string
    {
        return $this->resetKey;
    }

    public function setResetKey(?string $resetKey): static
    {
        $this->resetKey = $resetKey;
        return $this;
    }

    public function getResetExpire(): ?DateTimeImmutable
    {
        return $this->resetExpire;
    }

    public function setResetExpire(?DateTimeImmutable $resetExpire): static
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

    public function getFirstBuyDate(): ?DateTimeImmutable
    {
        return $this->firstBuyDate;
    }

    public function setFirstBuyDate(?DateTimeImmutable $firstBuyDate): static
    {
        $this->firstBuyDate = $firstBuyDate;
        return $this;
    }

    public function getLastBuyDate(): ?DateTimeImmutable
    {
        return $this->lastBuyDate;
    }

    public function setLastBuyDate(?DateTimeImmutable $lastBuyDate): static
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

    public function getNote(): ?string
    {
        return $this->note;
    }

    public function setNote(?string $note): static
    {
        $this->note = $note;
        return $this;
    }

    /**
     * @return CustomerAddress[]
     */
    public function getCustomerAddresses(): array
    {
        return $this->customerAddresses;
    }

    /**
     * @param CustomerAddress[] $customerAddresses
     */
    public function setCustomerAddresses(array $customerAddresses): static
    {
        $this->customerAddresses = $customerAddresses;
        return $this;
    }

    /**
     * @return Order[]
     */
    public function getOrders(): array
    {
        return $this->orders;
    }

    /**
     * @param Order[] $orders
     */
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
