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
    protected int|null $id = null;
    protected string $name = '';
    protected string|null $department = null;
    protected string $loginId = '';
    protected string|null $password = null;
    protected string|null $salt = null;
    protected Authority|null $authority = null;
    protected Work|null $work = null;
    protected int $sortNo = 0;
    protected DateTimeImmutable|null $loginDate = null;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
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

    public function getDepartment(): string|null
    {
        return $this->department;
    }

    public function setDepartment(string|null $department): static
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

    public function getAuthority(): Authority|null
    {
        return $this->authority;
    }

    public function setAuthority(Authority|null $authority): static
    {
        $this->authority = $authority;

        return $this;
    }

    public function getWork(): Work|null
    {
        return $this->work;
    }

    public function setWork(Work|null $work): static
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

    public function getLoginDate(): DateTimeImmutable|null
    {
        return $this->loginDate;
    }

    public function setLoginDate(DateTimeImmutable|null $loginDate): static
    {
        $this->loginDate = $loginDate;

        return $this;
    }
}
