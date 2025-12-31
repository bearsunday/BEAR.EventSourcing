<?php

declare(strict_types=1);

namespace BearEccube\Entity;

use DateTimeImmutable;

/**
 * News entity (新着情報)
 */
class News extends AbstractEntity
{
    protected ?int $id = null;
    protected ?DateTimeImmutable $publishDate = null;
    protected string $title = '';
    protected ?string $description = null;
    protected ?string $url = null;
    protected bool $linkMethod = false; // false = same window, true = new window
    protected bool $visible = true;

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getPublishDate(): ?DateTimeImmutable { return $this->publishDate; }
    public function setPublishDate(?DateTimeImmutable $publishDate): static { $this->publishDate = $publishDate; return $this; }

    public function getTitle(): string { return $this->title; }
    public function setTitle(string $title): static { $this->title = $title; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getUrl(): ?string { return $this->url; }
    public function setUrl(?string $url): static { $this->url = $url; return $this; }

    public function isLinkMethod(): bool { return $this->linkMethod; }
    public function setLinkMethod(bool $linkMethod): static { $this->linkMethod = $linkMethod; return $this; }

    public function isVisible(): bool { return $this->visible; }
    public function setVisible(bool $visible): static { $this->visible = $visible; return $this; }
}
