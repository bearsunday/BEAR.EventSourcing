<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Entity;

/**
 * Page layout entity (ページレイアウト)
 */
class PageLayout extends AbstractEntity
{
    protected int|null $pageId = null;
    protected Page|null $page = null;
    protected int|null $layoutId = null;
    protected Layout|null $layout = null;
    protected int $sortNo = 0;

    public function getPageId(): int|null
    {
        return $this->pageId;
    }

    public function setPageId(int|null $pageId): static
    {
        $this->pageId = $pageId;

        return $this;
    }

    public function getPage(): Page|null
    {
        return $this->page;
    }

    public function setPage(Page|null $page): static
    {
        $this->page = $page;

        return $this;
    }

    public function getLayoutId(): int|null
    {
        return $this->layoutId;
    }

    public function setLayoutId(int|null $layoutId): static
    {
        $this->layoutId = $layoutId;

        return $this;
    }

    public function getLayout(): Layout|null
    {
        return $this->layout;
    }

    public function setLayout(Layout|null $layout): static
    {
        $this->layout = $layout;

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
}
