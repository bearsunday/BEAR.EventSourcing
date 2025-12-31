<?php

declare(strict_types=1);

namespace BearEccube\Entity;

/**
 * Layout entity (レイアウト)
 */
class Layout extends AbstractEntity
{
    protected ?int $id = null;
    protected string $name = '';
    /** @var Block[] */
    protected array $blocks = [];

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    /** @return Block[] */
    public function getBlocks(): array { return $this->blocks; }
    /** @param Block[] $blocks */
    public function setBlocks(array $blocks): static { $this->blocks = $blocks; return $this; }
}
