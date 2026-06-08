<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Auth;

/**
 * Token storage interface
 */
interface TokenStorageInterface
{
    /**
     * Store token data
     *
     * @param array<string, mixed> $data
     */
    public function store(string $token, array $data): void;

    /**
     * Get token data
     *
     * @return array<string, mixed>|null
     */
    public function get(string $token): array|null;

    /**
     * Delete token
     */
    public function delete(string $token): void;

    /**
     * Clean expired tokens
     */
    public function cleanup(): void;
}
