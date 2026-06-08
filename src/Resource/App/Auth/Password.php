<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\EventSourcing\Auth\AuthServiceInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

use function strlen;

/**
 * Password reset resource
 */
class Password extends ResourceObject
{
    #[Inject]
    public function __construct(private AuthServiceInterface $authService)
    {
    }

    /**
     * Request password reset (send email)
     *
     * @param string $email Email address
     */
    public function onPost(string $email): static
    {
        $token = $this->authService->generateResetToken($email);

        // Always return success to prevent email enumeration
        $this->body = ['message' => 'If the email exists, a reset link has been sent.'];

        // In production, send email with token here
        // $this->mailService->sendPasswordReset($email, $token);

        return $this;
    }

    /**
     * Reset password with token
     *
     * @param string $token       Reset token
     * @param string $newPassword New password
     */
    public function onPut(string $token, string $newPassword): static
    {
        if (strlen($newPassword) < 8) {
            $this->code = 400;
            $this->body = ['error' => 'Password must be at least 8 characters'];

            return $this;
        }

        $result = $this->authService->resetPassword($token, $newPassword);

        if (! $result) {
            $this->code = 400;
            $this->body = ['error' => 'Invalid or expired reset token'];

            return $this;
        }

        $this->body = ['message' => 'Password has been reset successfully'];

        return $this;
    }
}
