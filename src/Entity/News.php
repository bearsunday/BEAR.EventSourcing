<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use DateTimeImmutable;

/**
 * News entity (新着情報)
 */
class News extends AbstractEntity
{
    protected int|null $id = null;
    protected DateTimeImmutable|null $publishDate = null;
    protected string $title = '';
    protected string|null $description = null;
    protected string|null $url = null;
    protected bool $linkMethod = false; // false = same window, true = new window
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

    public function getPublishDate(): DateTimeImmutable|null
    {
        return $this->publishDate;
    }

    public function setPublishDate(DateTimeImmutable|null $publishDate): static
    {
        $this->publishDate = $publishDate;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;

        return $this;
    }

    public function getDescription(): string|null
    {
        return $this->description;
    }

    public function setDescription(string|null $description): static
    {
        $this->description = $description;

        return $this;
    }

    public function getUrl(): string|null
    {
        return $this->url;
    }

    public function setUrl(string|null $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function isLinkMethod(): bool
    {
        return $this->linkMethod;
    }

    public function setLinkMethod(bool $linkMethod): static
    {
        $this->linkMethod = $linkMethod;

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
