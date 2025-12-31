<?php

declare(strict_types=1);

namespace BearEccube\Resource\App;

use BEAR\Resource\ResourceObject;
use BearEccube\Query\NewsQueryInterface;
use Ray\Di\Di\Inject;

/**
 * News resource (新着情報)
 */
class News extends ResourceObject
{
    private NewsQueryInterface $newsQuery;

    #[Inject]
    public function __construct(NewsQueryInterface $newsQuery)
    {
        $this->newsQuery = $newsQuery;
    }

    /**
     * Get news list
     *
     * @param int $page  Page number
     * @param int $limit Items per page
     */
    public function onGet(int $page = 1, int $limit = 10): static
    {
        $offset = ($page - 1) * $limit;

        $this->body = [
            'news' => $this->newsQuery->findAll($limit, $offset),
            'pagination' => [
                'page' => $page,
                'limit' => $limit,
                'total' => $this->newsQuery->count(),
            ],
        ];

        return $this;
    }

    /**
     * Create news (admin only)
     *
     * @param string      $title       News title
     * @param string|null $description News description
     * @param string|null $url         Link URL
     * @param bool        $linkMethod  Open in new window
     * @param string|null $publishDate Publish date (Y-m-d)
     */
    public function onPost(
        string $title,
        ?string $description = null,
        ?string $url = null,
        bool $linkMethod = false,
        ?string $publishDate = null
    ): static {
        $id = $this->newsQuery->create([
            'title' => $title,
            'description' => $description,
            'url' => $url,
            'link_method' => $linkMethod,
            'publish_date' => $publishDate ?? date('Y-m-d'),
            'visible' => true,
        ]);

        $this->code = 201;
        $this->headers['Location'] = "/news/{$id}";
        $this->body = ['id' => $id];

        return $this;
    }
}
