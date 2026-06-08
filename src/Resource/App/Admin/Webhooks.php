<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Annotation\RequireAuth;
use BEAR\EventSourcing\Query\WebhookQueryInterface;
use BEAR\Resource\ResourceObject;

class Webhooks extends ResourceObject
{
    public function __construct(
        private readonly WebhookQueryInterface $query,
    ) {
    }

    #[RequireAuth(role: 'admin')]
    public function onGet(int|null $id = null): static
    {
        if ($id !== null) {
            $webhook = $this->query->findById($id);
            if ($webhook === null) {
                $this->code = 404;
                $this->body = ['error' => 'Webhook not found'];

                return $this;
            }

            $webhook['logs'] = $this->query->getDeliveryLogs($id, 20);
            $this->body = $webhook;
        } else {
            $this->body = ['webhooks' => $this->query->findAll()];
        }

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPost(string $name, string $url, array $events = [], bool $enabled = true): static
    {
        $id = $this->query->create([
            'name' => $name,
            'url' => $url,
            'events' => $events,
            'enabled' => $enabled,
        ]);

        $webhook = $this->query->findById($id);

        $this->code = 201;
        $this->body = [
            'id' => $id,
            'secret' => $webhook['secret'],
        ];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onPut(
        int $id,
        string|null $name = null,
        string|null $url = null,
        array|null $events = null,
        bool|null $enabled = null,
    ): static {
        $webhook = $this->query->findById($id);
        if ($webhook === null) {
            $this->code = 404;
            $this->body = ['error' => 'Webhook not found'];

            return $this;
        }

        $data = [];
        if ($name !== null) {
            $data['name'] = $name;
        }

        if ($url !== null) {
            $data['url'] = $url;
        }

        if ($events !== null) {
            $data['events'] = $events;
        }

        if ($enabled !== null) {
            $data['enabled'] = $enabled ? 1 : 0;
        }

        if (! empty($data)) {
            $this->query->update($id, $data);
        }

        $this->code = 200;
        $this->body = ['id' => $id, 'updated' => true];

        return $this;
    }

    #[RequireAuth(role: 'admin')]
    public function onDelete(int $id): static
    {
        $webhook = $this->query->findById($id);
        if ($webhook === null) {
            $this->code = 404;
            $this->body = ['error' => 'Webhook not found'];

            return $this;
        }

        $this->query->delete($id);

        $this->code = 200;
        $this->body = ['deleted' => true];

        return $this;
    }
}
