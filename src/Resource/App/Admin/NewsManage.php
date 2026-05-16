<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\NewsQueryInterface;

class NewsManage extends ResourceObject
{
    public function __construct(
        private readonly NewsQueryInterface $query
    ) {}

    #[RequireAuth(role: 'admin')]
    public function onGet(?int $id = null, int $limit = 20, int $offset = 0): static
    {
        if ($id !== null) {
            $news = $this->query->findById($id);
            if ($news === null) {
                $this->code = 404;
                $this->body = ['error' => 'News not found'];
                return $this;
            }
            $this->body = $news;
        } else {
            $this->body = [
                'news' => $this->query->findAll($limit, $offset),
                'total' => $this->query->count(),
                'limit' => $limit,
                'offset' => $offset
            ];
        }
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(
        string $title,
        string $publish_date,
        ?string $comment = null,
        ?string $url = null,
        bool $link_method = false,
        bool $visible = true
    ): static {
        $id = $this->query->create([
            'title' => $title,
            'publish_date' => $publish_date,
            'comment' => $comment,
            'url' => $url,
            'link_method' => $link_method ? 1 : 0,
            'visible' => $visible ? 1 : 0,
        ]);

        $this->code = 201;
        $this->body = ['id' => $id];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        ?string $title = null,
        ?string $comment = null,
        ?string $url = null,
        ?bool $link_method = null,
        ?string $publish_date = null,
        ?bool $visible = null
    ): static {
        $news = $this->query->findById($id);
        if ($news === null) {
            $this->code = 404;
            $this->body = ['error' => 'News not found'];
            return $this;
        }

        $data = [];
        if ($title !== null) $data['title'] = $title;
        if ($comment !== null) $data['comment'] = $comment;
        if ($url !== null) $data['url'] = $url;
        if ($link_method !== null) $data['link_method'] = $link_method ? 1 : 0;
        if ($publish_date !== null) $data['publish_date'] = $publish_date;
        if ($visible !== null) $data['visible'] = $visible ? 1 : 0;

        if (!empty($data)) {
            $this->query->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];
        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $news = $this->query->findById($id);
        if ($news === null) {
            $this->code = 404;
            $this->body = ['error' => 'News not found'];
            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];
        return $this;
    }
}
