<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Block entity (ブロック)
 */
class Block extends AbstractEntity
{
    protected int|null $id = null;
    protected string $name = '';
    protected string|null $fileName = null;
    protected bool $useController = false;
    protected bool $deletable = true;

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

    public function getFileName(): string|null
    {
        return $this->fileName;
    }

    public function setFileName(string|null $fileName): static
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function isUseController(): bool
    {
        return $this->useController;
    }

    public function setUseController(bool $useController): static
    {
        $this->useController = $useController;

        return $this;
    }

    public function isDeletable(): bool
    {
        return $this->deletable;
    }

    public function setDeletable(bool $deletable): static
    {
        $this->deletable = $deletable;

        return $this;
    }
}
