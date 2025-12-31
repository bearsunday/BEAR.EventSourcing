<?php

declare(strict_types=1);

namespace BearEccube\Interceptor;

use BEAR\Resource\ResourceObject;
use BearEccube\Auth\AuthServiceInterface;
use Ray\Aop\MethodInterceptor;
use Ray\Aop\MethodInvocation;

/**
 * Authentication interceptor
 */
class AuthInterceptor implements MethodInterceptor
{
    public function __construct(
        private readonly AuthServiceInterface $authService
    ) {
    }

    public function invoke(MethodInvocation $invocation): mixed
    {
        /** @var ResourceObject $resource */
        $resource = $invocation->getThis();

        $token = $this->extractToken($resource);

        if ($token === null) {
            $resource->code = 401;
            $resource->body = ['error' => 'Authentication required'];
            return $resource;
        }

        $user = $this->authService->validateToken($token);

        if ($user === null) {
            $resource->code = 401;
            $resource->body = ['error' => 'Invalid or expired token'];
            return $resource;
        }

        // Inject user info into resource
        if (method_exists($resource, 'setAuthUser')) {
            $resource->setAuthUser($user);
        }

        return $invocation->proceed();
    }

    private function extractToken(ResourceObject $resource): ?string
    {
        // Try Authorization header
        $headers = $resource->headers ?? [];
        foreach ($headers as $name => $value) {
            if (strtolower($name) === 'authorization') {
                if (str_starts_with($value, 'Bearer ')) {
                    return substr($value, 7);
                }
            }
        }

        // Try query parameter
        if (isset($_GET['token'])) {
            return $_GET['token'];
        }

        return null;
    }
}
