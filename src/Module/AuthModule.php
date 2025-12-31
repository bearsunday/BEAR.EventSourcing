<?php

declare(strict_types=1);

namespace BearEccube\Module;

use BearEccube\Annotation\RequireAuth;
use BearEccube\Auth\AuthService;
use BearEccube\Auth\AuthServiceInterface;
use BearEccube\Auth\DbTokenStorage;
use BearEccube\Auth\TokenStorageInterface;
use BearEccube\Interceptor\AuthInterceptor;
use Ray\Di\AbstractModule;

/**
 * Authentication module - binds auth interfaces and sets up interceptors
 */
class AuthModule extends AbstractModule
{
    protected function configure(): void
    {
        // Bind auth service
        $this->bind(AuthServiceInterface::class)->to(AuthService::class);

        // Bind token storage
        $this->bind(TokenStorageInterface::class)->to(DbTokenStorage::class);

        // Bind JWT secret
        $this->bind()->annotatedWith('jwt_secret')->toInstance(
            getenv('JWT_SECRET') ?: 'your-secret-key-change-in-production'
        );

        // Bind authentication interceptor
        $this->bindInterceptor(
            $this->matcher->any(),
            $this->matcher->annotatedWith(RequireAuth::class),
            [AuthInterceptor::class]
        );
    }
}
