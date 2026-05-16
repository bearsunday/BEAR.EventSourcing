<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Page layout entity (ページレイアウト)
 */
class PageLayout extends AbstractEntity
{
    protected ?int $pageId = null;
    protected ?Page $page = null;
    protected ?int $layoutId = null;
    protected ?Layout $layout = null;
    protected int $sortNo = 0;

    public function getPageId(): ?int { return $this->pageId; }
    public function setPageId(?int $pageId): static { $this->pageId = $pageId; return $this; }

    public function getPage(): ?Page { return $this->page; }
    public function setPage(?Page $page): static { $this->page = $page; return $this; }

    public function getLayoutId(): ?int { return $this->layoutId; }
    public function setLayoutId(?int $layoutId): static { $this->layoutId = $layoutId; return $this; }

    public function getLayout(): ?Layout { return $this->layout; }
    public function setLayout(?Layout $layout): static { $this->layout = $layout; return $this; }

    public function getSortNo(): int { return $this->sortNo; }
    public function setSortNo(int $sortNo): static { $this->sortNo = $sortNo; return $this; }
}
