<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Query;

interface WebhookQueryInterface
{
    public function findById(int $id): array|null;

    public function findAll(): array;

    public function findByEvent(string $event): array;

    public function create(array $data): int;

    public function update(int $id, array $data): void;

    public function delete(int $id): void;

    public function logDelivery(int $webhookId, string $event, string $payload, int $responseCode, string|null $responseBody, bool $success): int;

    public function getDeliveryLogs(int $webhookId, int $limit = 50): array;
}
