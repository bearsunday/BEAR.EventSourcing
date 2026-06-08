<?php

declare(strict_types=1);

namespace BEAR\EventSourcing\Resource\App\Admin;

use BEAR\EventSourcing\Auth\AuthServiceInterface;
use BEAR\Resource\ResourceObject;

class Login extends ResourceObject
{
    public function __construct(
        private readonly AuthServiceInterface $auth,
    ) {
    }

    public function onPost(string $login_id, string $password): static
    {
        $result = $this->auth->authenticateMember($login_id, $password);

        if ($result === null) {
            $this->code = 401;
            $this->body = ['error' => 'Invalid credentials'];

            return $this;
        }

        $this->code = 200;
        $this->body = $result;

        return $this;
    }
}
