<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Auth\AuthServiceInterface;
use Ray\Di\Di\Inject;

/**
 * Logout resource
 */
class Logout extends ResourceObject
{
    private AuthServiceInterface $authService;

    #[Inject]
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
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
