<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Class name entity (規格)
 */
class ClassName extends AbstractEntity
{
    protected int|null $id = null;
    protected string $name = '';
    protected string $backendName = '';
    protected int $sortNo = 0;
    /** @var ClassCategory[] */
    protected array $classCategories = [];

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

    public function getBackendName(): string
    {
        return $this->backendName;
    }

    public function setBackendName(string $backendName): static
    {
        $this->backendName = $backendName;

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

    /** @return ClassCategory[] */
    public function getClassCategories(): array
    {
        return $this->classCategories;
    }

    /** @param ClassCategory[] $classCategories */
    public function setClassCategories(array $classCategories): static
    {
        $this->classCategories = $classCategories;

        return $this;
    }
}
