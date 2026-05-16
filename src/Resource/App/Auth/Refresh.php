<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Auth\AuthServiceInterface;
use Ray\Di\Di\Inject;

/**
 * Token refresh resource
 */
class Refresh extends ResourceObject
{
    private AuthServiceInterface $authService;

    #[Inject]
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Refresh token
     *
     * @param string $token Current auth token
     */
    public function onPost(string $token): static
    {
        $newToken = $this->authService->refreshToken($token);

        if ($newToken === null) {
            $this->code = 401;
            $this->body = ['error' => 'Invalid or expired token'];
            return $this;
        }

        $this->body = ['token' => $newToken];
        return $this;
    }
}
