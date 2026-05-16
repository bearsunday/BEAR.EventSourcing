<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

use BEAR\EventSourcing\Entity\Master\PageType;

/**
 * Page entity (ページ)
 */
class Page extends AbstractEntity
{
    protected ?int $id = null;
    protected ?PageType $pageType = null;
    protected string $name = '';
    protected string $url = '';
    protected ?string $fileName = null;
    protected ?string $author = null;
    protected ?string $description = null;
    protected ?string $keyword = null;
    protected ?string $metaRobots = null;
    protected ?string $metaTags = null;
    protected int $editType = 0; // 0: user, 1: system
    /** @var PageLayout[] */
    protected array $pageLayouts = [];

    public function getId(): ?int { return $this->id; }
    public function setId(?int $id): static { $this->id = $id; return $this; }

    public function getPageType(): ?PageType { return $this->pageType; }
    public function setPageType(?PageType $pageType): static { $this->pageType = $pageType; return $this; }

    public function getName(): string { return $this->name; }
    public function setName(string $name): static { $this->name = $name; return $this; }

    public function getUrl(): string { return $this->url; }
    public function setUrl(string $url): static { $this->url = $url; return $this; }

    public function getFileName(): ?string { return $this->fileName; }
    public function setFileName(?string $fileName): static { $this->fileName = $fileName; return $this; }

    public function getAuthor(): ?string { return $this->author; }
    public function setAuthor(?string $author): static { $this->author = $author; return $this; }

    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): static { $this->description = $description; return $this; }

    public function getKeyword(): ?string { return $this->keyword; }
    public function setKeyword(?string $keyword): static { $this->keyword = $keyword; return $this; }

    public function getMetaRobots(): ?string { return $this->metaRobots; }
    public function setMetaRobots(?string $metaRobots): static { $this->metaRobots = $metaRobots; return $this; }

    public function getMetaTags(): ?string { return $this->metaTags; }
    public function setMetaTags(?string $metaTags): static { $this->metaTags = $metaTags; return $this; }

    public function getEditType(): int { return $this->editType; }
    public function setEditType(int $editType): static { $this->editType = $editType; return $this; }

    /** @return PageLayout[] */
    public function getPageLayouts(): array { return $this->pageLayouts; }
    /** @param PageLayout[] $pageLayouts */
    public function setPageLayouts(array $pageLayouts): static { $this->pageLayouts = $pageLayouts; return $this; }
}
