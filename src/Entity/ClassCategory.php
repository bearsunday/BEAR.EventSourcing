<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Class category entity (規格分類)
 */
class ClassCategory extends AbstractEntity
{
    protected int|null $id = null;
    protected int|null $classNameId = null;
    protected ClassName|null $className = null;
    protected string $name = '';
    protected string $backendName = '';
    protected int $sortNo = 0;
    protected bool $visible = true;

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getClassNameId(): int|null
    {
        return $this->classNameId;
    }

    public function setClassNameId(int|null $classNameId): static
    {
        $this->classNameId = $classNameId;

        return $this;
    }

    public function getClassName(): ClassName|null
    {
        return $this->className;
    }

    public function setClassName(ClassName|null $className): static
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
