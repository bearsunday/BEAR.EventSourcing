<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Auth;

use BEAR\EventSourcing\Auth\AuthServiceInterface;
use BEAR\Resource\ResourceObject;
use Ray\Di\Di\Inject;

/**
 * Customer login resource
 */
class Login extends ResourceObject
{
    #[Inject]
    public function __construct(private AuthServiceInterface $authService)
    {
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
