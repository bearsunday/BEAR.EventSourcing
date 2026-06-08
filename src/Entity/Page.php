<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\PageType;

/**
 * Page entity (ページ)
 */
class Page extends AbstractEntity
{
    protected int|null $id = null;
    protected PageType|null $pageType = null;
    protected string $name = '';
    protected string $url = '';
    protected string|null $fileName = null;
    protected string|null $author = null;
    protected string|null $description = null;
    protected string|null $keyword = null;
    protected string|null $metaRobots = null;
    protected string|null $metaTags = null;
    protected int $editType = 0; // 0: user, 1: system
    /** @var PageLayout[] */
    protected array $pageLayouts = [];

    public function getId(): int|null
    {
        return $this->id;
    }

    public function setId(int|null $id): static
    {
        $this->id = $id;

        return $this;
    }

    public function getPageType(): PageType|null
    {
        return $this->pageType;
    }

    public function setPageType(PageType|null $pageType): static
    {
        $this->pageType = $pageType;

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

    public function getUrl(): string
    {
        return $this->url;
    }

    public function setUrl(string $url): static
    {
        $this->url = $url;

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

    public function getAuthor(): string|null
    {
        return $this->author;
    }

    public function setAuthor(string|null $author): static
    {
        $this->author = $author;

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

    public function getKeyword(): string|null
    {
        return $this->keyword;
    }

    public function setKeyword(string|null $keyword): static
    {
        $this->keyword = $keyword;

        return $this;
    }

    public function getMetaRobots(): string|null
    {
        return $this->metaRobots;
    }

    public function setMetaRobots(string|null $metaRobots): static
    {
        $this->metaRobots = $metaRobots;

        return $this;
    }

    public function getMetaTags(): string|null
    {
        return $this->metaTags;
    }

    public function setMetaTags(string|null $metaTags): static
    {
        $this->metaTags = $metaTags;

        return $this;
    }

    public function getEditType(): int
    {
        return $this->editType;
    }

    public function setEditType(int $editType): static
    {
        $this->editType = $editType;

        return $this;
    }

    /** @return PageLayout[] */
    public function getPageLayouts(): array
    {
        return $this->pageLayouts;
    }

    /** @param PageLayout[] $pageLayouts */
    public function setPageLayouts(array $pageLayouts): static
    {
        $this->pageLayouts = $pageLayouts;

        return $this;
    }
}
