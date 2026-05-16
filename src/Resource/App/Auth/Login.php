<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\Resource\ResourceObject;
use BEAR\EventSourcing\Auth\AuthServiceInterface;
use Ray\Di\Di\Inject;

/**
 * Customer login resource
 */
class Login extends ResourceObject
{
    private AuthServiceInterface $authService;

    #[Inject]
    public function __construct(AuthServiceInterface $authService)
    {
        $this->authService = $authService;
    }

    /**
     * Login with email and password
     *
     * @param string $email    Email address
     * @param string $password Password
     */
    public function onPost(string $email, string $password): static
    {
        $result = $this->authService->authenticateCustomer($email, $password);

        if ($result === null) {
            $this->code = 401;
            $this->body = ['error' => 'Invalid email or password'];
            return $this;
        }

        $this->body = $result;
        return $this;
    }
}
