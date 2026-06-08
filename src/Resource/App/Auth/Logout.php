<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\EventSourcing\Auth\AuthServiceInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Logout resource
 */
class Logout extends ResourceObject
{
    #[Inject]
    public function __construct(private AuthServiceInterface $authService)
    {
    }

    /**
     * Logout (revoke token)
     *
     * @param string $token Auth token
     */
    public function onPost(string $token): static
    {
        $this->authService->revokeToken($token);

        $this->code = 204;
        $this->body = null;

        return $this;
    }
}
