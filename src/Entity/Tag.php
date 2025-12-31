<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Tag entity (タグ)
 */
class Tag extends AbstractEntity
{
    protected ?int $id = null;
    protected string $name = '';
    protected int $sortNo = 0;

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

    public function getSortNo(): int
    {
        return $this->sortNo;
    }

    public function setSortNo(int $sortNo): static
    {
        $this->sortNo = $sortNo;
        return $this;
    }
}
