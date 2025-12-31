<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\NewsQueryInterface;
use Ray\Di\Di\Inject;

/**
 * News item resource (新着情報詳細)
 */
class NewsItem extends ResourceObject
{
    private NewsQueryInterface $newsQuery;

    #[Inject]
    public function __construct(NewsQueryInterface $newsQuery)
    {
        $this->newsQuery = $newsQuery;
    }

    /**
     * Get news by ID
     *
     * @param int $id News ID
     */
    public function onGet(int $id): static
    {
        $news = $this->newsQuery->findById($id);

        if ($news === null) {
            $this->code = 404;
            $this->body = ['error' => 'News not found'];
            return $this;
        }

        $this->body = $news;
        return $this;
    }

    /**
     * Update news
     *
     * @param int         $id          News ID
     * @param string|null $title       News title
     * @param string|null $description News description
     * @param string|null $url         Link URL
     * @param bool|null   $visible     Visibility
     */
    public function onPut(
        int $id,
        ?string $title = null,
        ?string $description = null,
        ?string $url = null,
        ?bool $visible = null
    ): static {
        $news = $this->newsQuery->findById($id);

        if ($news === null) {
            $this->code = 404;
            $this->body = ['error' => 'News not found'];
            return $this;
        }

        $data = array_filter([
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'visible' => $visible,
        ], fn($v) => $v !== null);

        $this->newsQuery->update($id, $data);
        $this->body = $this->newsQuery->findById($id);

        return $this;
    }

    /**
     * Delete news
     *
     * @param int $id News ID
     */
    public function onDelete(int $id): static
    {
        $news = $this->newsQuery->findById($id);

        if ($news === null) {
            $this->code = 404;
            $this->body = ['error' => 'News not found'];
            return $this;
        }

        $this->newsQuery->delete($id);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
