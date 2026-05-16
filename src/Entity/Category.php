<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Category entity (カテゴリ)
 */
class Category extends AbstractEntity
{
    protected ?int $id = null;
    protected string $name = '';
    protected int $sortNo = 0;
    protected int $level = 1;
    protected ?int $parentId = null;
    protected ?Category $parent = null;
    /** @var Category[] */
    protected array $children = [];

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

    public function getLevel(): int
    {
        return $this->level;
    }

    public function setLevel(int $level): static
    {
        $this->level = $level;
        return $this;
    }

    public function getParentId(): ?int
    {
        return $this->parentId;
    }

    public function setParentId(?int $parentId): static
    {
        $this->parentId = $parentId;
        return $this;
    }

    public function getParent(): ?Category
    {
        return $this->parent;
    }

    public function setParent(?Category $parent): static
    {
        $this->parent = $parent;
        return $this;
    }

    /**
     * @return Category[]
     */
    public function getChildren(): array
    {
        return $this->children;
    }

    /**
     * @param Category[] $children
     */
    public function setChildren(array $children): static
    {
        $this->children = $children;
        return $this;
    }

    public function addChild(Category $child): static
    {
        $this->children[] = $child;
        return $this;
    }

    /**
     * Get full path name (e.g., "親カテゴリ > 子カテゴリ")
     */
    public function getPathName(): string
    {
        $paths = [];
        $current = $this;

        while ($current !== null) {
            array_unshift($paths, $current->getName());
            $current = $current->getParent();
        }

        return implode(' > ', $paths);
    }
}
