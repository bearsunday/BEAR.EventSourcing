<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Class category entity (規格分類)
 */
class ClassCategory extends AbstractEntity
{
    protected ?int $id = null;
    protected ?int $classNameId = null;
    protected ?ClassName $className = null;
    protected string $name = '';
    protected string $backendName = '';
    protected int $sortNo = 0;
    protected bool $visible = true;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setId(?int $id): static
    {
        $this->id = $id;
        return $this;
    }

    public function getClassNameId(): ?int
    {
        return $this->classNameId;
    }

    public function setClassNameId(?int $classNameId): static
    {
        $this->classNameId = $classNameId;
        return $this;
    }

    public function getClassName(): ?ClassName
    {
        return $this->className;
    }

    public function setClassName(?ClassName $className): static
    {
        $this->className = $className;
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

    public function isVisible(): bool
    {
        return $this->visible;
    }

    public function setVisible(bool $visible): static
    {
        $this->visible = $visible;
        return $this;
    }
}
