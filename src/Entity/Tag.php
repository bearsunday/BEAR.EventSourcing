<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Tag entity (タグ)
 */
class Tag extends AbstractEntity
{
    protected int|null $id = null;
    protected string $name = '';
    protected int $sortNo = 0;

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
