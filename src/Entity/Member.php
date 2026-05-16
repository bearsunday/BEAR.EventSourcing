<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\Authority;
use BEAR\EventSourcing\Entity\Master\Work;
use DateTimeImmutable;

/**
 * Admin member entity (管理者)
 */
class Member extends AbstractEntity
{
    protected ?int $id = null;
    protected string $name = '';
    protected ?string $department = null;
    protected string $loginId = '';
    protected ?string $password = null;
    protected ?string $salt = null;
    protected ?Authority $authority = null;
    protected ?Work $work = null;
    protected int $sortNo = 0;
    protected ?DateTimeImmutable $loginDate = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getDepartment(): ?string
    {
        return $this->department;
    }

    public function setDepartment(?string $department): static
    {
        $this->department = $department;
        return $this;
    }

    public function getLoginId(): string
    {
        return $this->loginId;
    }

    public function setLoginId(string $loginId): static
    {
        $this->loginId = $loginId;
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

    public function getAuthority(): ?Authority
    {
        return $this->authority;
    }

    public function setAuthority(?Authority $authority): static
    {
        $this->authority = $authority;
        return $this;
    }

    public function getWork(): ?Work
    {
        return $this->work;
    }

    public function setWork(?Work $work): static
    {
        $this->work = $work;
        return $this;
    }

    public function getSortNo(): int
    {
        return $this->sortNo;
    }

    public function setSortNo(int $sortNo): static
    {
        $this->sortNo = $sortNo;
        return $this;
    }

    public function getLoginDate(): ?DateTimeImmutable
    {
        return $this->loginDate;
    }

    public function setLoginDate(?DateTimeImmutable $loginDate): static
    {
        $this->loginDate = $loginDate;
        return $this;
    }
}
