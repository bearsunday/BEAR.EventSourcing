<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Auth;

use Aura\Sql\ExtendedPdo;
use DateTimeImmutable;

use function hash;

/**
 * Database-backed token storage
 */
class DbTokenStorage implements TokenStorageInterface
{
    public function __construct(
        private readonly ExtendedPdo $pdo,
    ) {
    }

    public function store(string $token, array $data): void
    {
        $sql = 'INSERT INTO auth_token (token, user_type, user_id, expires_at, created_at)
                VALUES (:token, :user_type, :user_id, :expires_at, :created_at)
                ON DUPLICATE KEY UPDATE expires_at = :expires_at';

        $this->pdo->perform($sql, [
            'token' => hash('sha256', $token),
            'user_type' => $data['type'],
            'user_id' => $data['id'],
            'expires_at' => $data['expires_at'],
            'created_at' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }

    public function get(string $token): array|null
    {
        $sql = 'SELECT user_type, user_id, expires_at FROM auth_token WHERE token = :token';
        $result = $this->pdo->fetchOne($sql, ['token' => hash('sha256', $token)]);

        if (! $result) {
            return null;
        }

        return [
            'type' => $result['user_type'],
            'id' => (int) $result['user_id'],
            'expires_at' => $result['expires_at'],
        ];
    }

    public function delete(string $token): void
    {
        $this->pdo->perform('DELETE FROM auth_token WHERE token = :token', [
            'token' => hash('sha256', $token),
        ]);
    }

    public function cleanup(): void
    {
        $this->pdo->perform('DELETE FROM auth_token WHERE expires_at < :now', [
            'now' => (new DateTimeImmutable())->format('Y-m-d H:i:s'),
        ]);
    }
}
