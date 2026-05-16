<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Auth;

use BEAR\EventSourcing\Query\CustomerQueryInterface;
use BEAR\EventSourcing\Query\MemberQueryInterface;
use DateTimeImmutable;
use Ramsey\Uuid\Uuid;

/**
 * JWT-based authentication service
 */
class AuthService implements AuthServiceInterface
{
    private const TOKEN_EXPIRY_HOURS = 24;
    private const RESET_TOKEN_EXPIRY_HOURS = 1;

    public function __construct(
        private readonly CustomerQueryInterface $customerQuery,
        private readonly MemberQueryInterface $memberQuery,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly string $secretKey = 'your-secret-key-change-in-production',
    ) {
    }

    public function authenticateCustomer(string $email, string $password): ?array
    {
        $customer = $this->customerQuery->findByEmail($email);

        if ($customer === null) {
            return null;
        }

        if (!password_verify($password, $customer['password'])) {
            return null;
        }

        $token = $this->generateToken('customer', $customer['id']);
        $this->tokenStorage->store($token, [
            'type' => 'customer',
            'id' => $customer['id'],
            'expires_at' => (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY_HOURS . ' hours')->format('Y-m-d H:i:s'),
        ]);

        unset($customer['password'], $customer['salt']);

        return [
            'token' => $token,
            'customer' => $customer,
        ];
    }

    public function authenticateMember(string $loginId, string $password): ?array
    {
        $member = $this->memberQuery->findByLoginId($loginId);

        if ($member === null) {
            return null;
        }

        if (!password_verify($password, $member['password'])) {
            return null;
        }

        $token = $this->generateToken('member', $member['id']);
        $this->tokenStorage->store($token, [
            'type' => 'member',
            'id' => $member['id'],
            'expires_at' => (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY_HOURS . ' hours')->format('Y-m-d H:i:s'),
        ]);

        unset($member['password'], $member['salt']);

        return [
            'token' => $token,
            'member' => $member,
        ];
    }

    public function validateToken(string $token): ?array
    {
        $data = $this->tokenStorage->get($token);

        if ($data === null) {
            return null;
        }

        $expiresAt = new DateTimeImmutable($data['expires_at']);
        if ($expiresAt < new DateTimeImmutable()) {
            $this->tokenStorage->delete($token);
            return null;
        }

        if ($data['type'] === 'customer') {
            $user = $this->customerQuery->findById($data['id']);
        } else {
            $user = $this->memberQuery->findById($data['id']);
        }

        if ($user === null) {
            return null;
        }

        unset($user['password'], $user['salt']);

        return [
            'type' => $data['type'],
            'id' => $data['id'],
            'data' => $user,
        ];
    }

    public function refreshToken(string $token): ?string
    {
        $data = $this->tokenStorage->get($token);

        if ($data === null) {
            return null;
        }

        $this->tokenStorage->delete($token);

        $newToken = $this->generateToken($data['type'], $data['id']);
        $this->tokenStorage->store($newToken, [
            'type' => $data['type'],
            'id' => $data['id'],
            'expires_at' => (new DateTimeImmutable())->modify('+' . self::TOKEN_EXPIRY_HOURS . ' hours')->format('Y-m-d H:i:s'),
        ]);

        return $newToken;
    }

    public function revokeToken(string $token): void
    {
        $this->tokenStorage->delete($token);
    }

    public function generateResetToken(string $email): ?string
    {
        $customer = $this->customerQuery->findByEmail($email);

        if ($customer === null) {
            return null;
        }

        $resetToken = Uuid::uuid4()->toString();
        $expiresAt = (new DateTimeImmutable())->modify('+' . self::RESET_TOKEN_EXPIRY_HOURS . ' hours');

        $this->customerQuery->update($customer['id'], [
            'reset_key' => $resetToken,
            'reset_expire' => $expiresAt->format('Y-m-d H:i:s'),
        ]);

        return $resetToken;
    }

    public function resetPassword(string $token, string $newPassword): bool
    {
        $customer = $this->customerQuery->findByResetToken($token);

        if ($customer === null) {
            return false;
        }

        $expiresAt = new DateTimeImmutable($customer['reset_expire']);
        if ($expiresAt < new DateTimeImmutable()) {
            return false;
        }

        $this->customerQuery->update($customer['id'], [
            'password' => password_hash($newPassword, PASSWORD_DEFAULT),
            'reset_key' => null,
            'reset_expire' => null,
        ]);

        return true;
    }

    private function generateToken(string $type, int $id): string
    {
        $payload = base64_encode(json_encode([
            'type' => $type,
            'id' => $id,
            'iat' => time(),
            'jti' => Uuid::uuid4()->toString(),
        ]));

        $signature = hash_hmac('sha256', $payload, $this->secretKey);

        return $payload . '.' . $signature;
    }
}
