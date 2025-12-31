<?php

declare(strict_types=1);

namespace BearEccube\Auth;

/**
 * Authentication service interface
 */
interface AuthServiceInterface
{
    /**
     * Authenticate customer by email and password
     *
     * @return array{token: string, customer: array<string, mixed>}|null
     */
    public function authenticateCustomer(string $email, string $password): ?array;

    /**
     * Authenticate admin member by login ID and password
     *
     * @return array{token: string, member: array<string, mixed>}|null
     */
    public function authenticateMember(string $loginId, string $password): ?array;

    /**
     * Validate token and return user info
     *
     * @return array{type: string, id: int, data: array<string, mixed>}|null
     */
    public function validateToken(string $token): ?array;

    /**
     * Refresh token
     */
    public function refreshToken(string $token): ?string;

    /**
     * Revoke token
     */
    public function revokeToken(string $token): void;

    /**
     * Generate password reset token
     */
    public function generateResetToken(string $email): ?string;

    /**
     * Reset password with token
     */
    public function resetPassword(string $token, string $newPassword): bool;
}
