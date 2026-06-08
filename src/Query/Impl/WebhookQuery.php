<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query\Impl;

use Aura\Sql\ExtendedPdo;
use BEAR\EventSourcing\Query\WebhookQueryInterface;
use DateTimeImmutable;

use function array_keys;
use function array_map;
use function bin2hex;
use function implode;
use function json_decode;
use function json_encode;
use function random_bytes;

class WebhookQuery implements WebhookQueryInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo,
    ) {
    }

    public function findById(int $id): array|null
    {
        $result = $this->pdo->fetchOne('SELECT * FROM webhook WHERE id = :id', ['id' => $id]);
        if ($result) {
            $result['events'] = json_decode($result['events'] ?? '[]', true);
        }

        return $result ?: null;
    }

    public function findAll(): array
    {
        $results = $this->pdo->fetchAll('SELECT * FROM webhook ORDER BY id ASC');
        foreach ($results as &$result) {
            $result['events'] = json_decode($result['events'] ?? '[]', true);
        }

        return $results;
    }

    public function findByEvent(string $event): array
    {
        $results = $this->pdo->fetchAll(
            "SELECT * FROM webhook WHERE enabled = 1 AND JSON_CONTAINS(events, :event, '$')",
            ['event' => json_encode($event)],
        );
        foreach ($results as &$result) {
            $result['events'] = json_decode($result['events'] ?? '[]', true);
        }

        return $results;
    }

    public function create(array $data): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO webhook (name, url, secret, events, enabled, create_date, update_date)
             VALUES (:name, :url, :secret, :events, :enabled, :create_date, :update_date)',
            [
                'name' => $data['name'],
                'url' => $data['url'],
                'secret' => $data['secret'] ?? bin2hex(random_bytes(32)),
                'events' => json_encode($data['events'] ?? []),
                'enabled' => $data['enabled'] ?? 1,
                'create_date' => $now,
                'update_date' => $now,
            ],
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function update(int $id, array $data): void
    {
        if (isset($data['events'])) {
            $data['events'] = json_encode($data['events']);
        }

        $data['update_date'] = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $sets = array_map(static fn ($k) => "{$k} = :{$k}", array_keys($data));
        $data['id'] = $id;
        $this->pdo->perform('UPDATE webhook SET ' . implode(', ', $sets) . ' WHERE id = :id', $data);
    }

    public function delete(int $id): void
    {
        $this->pdo->perform('DELETE FROM webhook WHERE id = :id', ['id' => $id]);
    }

    public function logDelivery(int $webhookId, string $event, string $payload, int $responseCode, string|null $responseBody, bool $success): int
    {
        $now = (new DateTimeImmutable())->format('Y-m-d H:i:s');
        $this->pdo->perform(
            'INSERT INTO webhook_log (webhook_id, event, payload, response_code, response_body, success, create_date)
             VALUES (:webhook_id, :event, :payload, :response_code, :response_body, :success, :create_date)',
            [
                'webhook_id' => $webhookId,
                'event' => $event,
                'payload' => $payload,
                'response_code' => $responseCode,
                'response_body' => $responseBody,
                'success' => $success ? 1 : 0,
                'create_date' => $now,
            ],
        );

        return (int) $this->pdo->lastInsertId();
    }

    public function getDeliveryLogs(int $webhookId, int $limit = 50): array
    {
        return $this->pdo->fetchAll(
            'SELECT * FROM webhook_log WHERE webhook_id = :webhook_id ORDER BY create_date DESC LIMIT :limit',
            ['webhook_id' => $webhookId, 'limit' => $limit],
        );
    }
}
